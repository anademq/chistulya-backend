<?php

namespace App\Enums;

enum DailyTaskScope: string
{
    case GLOBAL = 'global';
    case PARENT = 'parent';
    case ASSIGNED = 'assigned';
}
