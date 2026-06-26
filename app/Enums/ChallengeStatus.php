<?php

namespace App\Enums;

enum ChallengeStatus: string
{
    case SELECTED = 'selected';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REWARD_CLAIMED = 'reward_claimed';
}
