<?php

namespace App\Enums;

enum AchievementStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REWARD_CLAIMED = 'reward_claimed';
}
