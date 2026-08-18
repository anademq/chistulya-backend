<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Provision the object storage bucket the application uploads to.
 *
 * Replaces the standalone `minio-init` compose service: everything here runs
 * through the AWS SDK that ships with league/flysystem-aws-s3-v3, so no extra
 * image, service or `mc` binary is needed. Safe to run on every boot — each
 * step checks the current state before changing anything.
 */
class StorageProvision extends Command
{
    protected $signature = 'storage:provision
                            {--disk=s3 : Filesystem disk to provision}
                            {--tmp-expire-days=1 : Days before objects under the temporary prefix expire}';

    protected $description = 'Create the storage bucket, lock down public access and install the temporary-upload lifecycle rule.';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $bucket = (string) config("filesystems.disks.{$disk}.bucket");

        if ($bucket === '') {
            $this->components->error("Disk [{$disk}] has no bucket configured.");

            return self::FAILURE;
        }

        try {
            $client = $this->clientFor($disk);

            $this->ensureBucketExists($client, $bucket);
            $this->ensureBucketIsPrivate($client, $bucket);
            $this->ensureTemporaryUploadsExpire($client, $bucket);
        } catch (AwsException $e) {
            $this->components->error("Storage provisioning failed: {$e->getAwsErrorMessage()}");

            return self::FAILURE;
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Storage bucket [{$bucket}] is provisioned.");

        return self::SUCCESS;
    }

    private function clientFor(string $disk): S3Client
    {
        $adapter = Storage::disk($disk);

        if (! $adapter instanceof AwsS3V3Adapter) {
            throw new RuntimeException("Disk [{$disk}] is not an S3-compatible disk.");
        }

        return $adapter->getClient();
    }

    private function ensureBucketExists(S3Client $client, string $bucket): void
    {
        if ($client->doesBucketExistV2($bucket, accept403: true)) {
            $this->components->twoColumnDetail('Bucket', 'already exists');

            return;
        }

        $client->createBucket(['Bucket' => $bucket]);
        $client->waitUntil('BucketExists', ['Bucket' => $bucket]);

        $this->components->twoColumnDetail('Bucket', '<info>created</info>');
    }

    /**
     * Media is served through pre-signed URLs, so the bucket must never be
     * world-readable. Buckets start private on both S3 and MinIO; this makes
     * that explicit and survives someone opening it by hand.
     *
     * Older MinIO builds do not implement PutPublicAccessBlock. That is not a
     * reason to fail a deployment, so it degrades to a warning.
     */
    private function ensureBucketIsPrivate(S3Client $client, string $bucket): void
    {
        try {
            $client->putPublicAccessBlock([
                'Bucket' => $bucket,
                'PublicAccessBlockConfiguration' => [
                    'BlockPublicAcls' => true,
                    'IgnorePublicAcls' => true,
                    'BlockPublicPolicy' => true,
                    'RestrictPublicBuckets' => true,
                ],
            ]);
        } catch (AwsException $e) {
            if (! in_array($e->getAwsErrorCode(), ['NotImplemented', 'MethodNotAllowed'], true)) {
                throw $e;
            }

            $this->components->warn('Storage backend does not support PutPublicAccessBlock; relying on the default private bucket policy.');

            return;
        }

        $this->components->twoColumnDetail('Public access', '<info>blocked</info>');
    }

    /**
     * Uploads land under the temporary prefix and are moved to the permanent one
     * once attached to an entity. Anything left behind is abandoned, so storage
     * expires it automatically — this backs up MediaService::cleanupOrphans(),
     * which only removes the database rows.
     */
    private function ensureTemporaryUploadsExpire(S3Client $client, string $bucket): void
    {
        $prefix = (string) config('media.tmp_prefix');
        $days = max(1, (int) $this->option('tmp-expire-days'));
        $ruleId = 'expire-temporary-uploads';

        $rules = $this->existingLifecycleRules($client, $bucket);

        $desired = [
            'ID' => $ruleId,
            'Status' => 'Enabled',
            'Filter' => ['Prefix' => $prefix],
            'Expiration' => ['Days' => $days],
        ];

        $current = collect($rules)->firstWhere('ID', $ruleId);

        if ($current == $desired) {
            $this->components->twoColumnDetail('Lifecycle rule', 'already up to date');

            return;
        }

        $merged = collect($rules)
            ->reject(static fn (array $rule): bool => ($rule['ID'] ?? null) === $ruleId)
            ->push($desired)
            ->values()
            ->all();

        $client->putBucketLifecycleConfiguration([
            'Bucket' => $bucket,
            'LifecycleConfiguration' => ['Rules' => $merged],
        ]);

        $this->components->twoColumnDetail('Lifecycle rule', "<info>{$prefix}* expires after {$days}d</info>");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function existingLifecycleRules(S3Client $client, string $bucket): array
    {
        try {
            $result = $client->getBucketLifecycleConfiguration(['Bucket' => $bucket]);

            return $result['Rules'] ?? [];
        } catch (AwsException $e) {
            // No configuration yet — S3 and MinIO both answer with this code.
            if ($e->getAwsErrorCode() === 'NoSuchLifecycleConfiguration') {
                return [];
            }

            throw $e;
        }
    }
}
