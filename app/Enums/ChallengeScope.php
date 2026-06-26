<?php

namespace App\Enums;

enum ChallengeScope: string
{
    case GLOBAL = 'global';
    case PARENT = 'parent';
    case ASSIGNED = 'assigned';
}
