<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exp;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class RewardService
{
    private const XP_PER_LEVEL = 100;

    /**
     * @return array{level:int,xp:int,coins:int,granted_xp:int,granted_coins:int}
     */
    public function grant(User $child, int $xp, int $coins): array
    {
        return DB::transaction(function () use ($child, $xp, $coins): array {
            $exp = Exp::lockForUpdate()->firstOrCreate(
                ['child_id' => $child->id],
                ['level' => 1, 'xp' => 0]
            );

            $wallet = Wallet::lockForUpdate()->firstOrCreate(
                ['child_id' => $child->id],
                ['coins' => 0]
            );

            $grantedXp = max(0, $xp);
            $grantedCoins = max(0, $coins);

            $newTotalXp = max(0, $exp->xp + $grantedXp);
            $newLevel = intdiv($newTotalXp, self::XP_PER_LEVEL) + 1;

            $exp->forceFill([
                'xp' => $newTotalXp,
                'level' => max(1, $newLevel),
            ])->save();

            $wallet->forceFill([
                'coins' => max(0, $wallet->coins + $grantedCoins),
            ])->save();

            return [
                'level' => (int) $exp->level,
                'xp' => (int) $exp->xp,
                'coins' => (int) $wallet->coins,
                'granted_xp' => $grantedXp,
                'granted_coins' => $grantedCoins,
            ];
        });
    }
}

