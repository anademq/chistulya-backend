#!/usr/bin/env sh
# Runs automatically via the minio-init service on every `docker compose up`.
# MinIO is guaranteed healthy before this script executes (depends_on condition).

set -e

: "${AWS_ACCESS_KEY_ID:?AWS_ACCESS_KEY_ID is required}"
: "${AWS_SECRET_ACCESS_KEY:?AWS_SECRET_ACCESS_KEY is required}"
: "${AWS_BUCKET:?AWS_BUCKET is required}"

mc alias set myminio http://minio:9000 "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY"

mc mb --ignore-existing "myminio/$AWS_BUCKET"

mc anonymous set none "myminio/$AWS_BUCKET"

rules=$(mc ilm rule ls "myminio/$AWS_BUCKET" 2>/dev/null)
case "$rules" in
    *"tmp/"*)
        echo "Lifecycle rule for tmp/ already exists."
        ;;
    *)
        mc ilm rule add --prefix "tmp/" --expire-days 1 "myminio/$AWS_BUCKET"
        echo "Lifecycle rule set: tmp/* expires after 24 hours."
        ;;
esac

echo "MinIO setup complete."
