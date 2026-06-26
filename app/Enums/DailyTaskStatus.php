<?php

namespace App\Enums;

enum DailyTaskStatus: string
{
    case SELECTED = 'selected';
    case COMPLETED = 'completed';
    case REWARD_CLAIMED = 'reward_claimed';
}
