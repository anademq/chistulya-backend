<?php

namespace App\Enums;

enum ReminderScope: string
{
    case GLOBAL = 'global';
    case PARENT = 'parent';
    case ASSIGNED = 'assigned';
    case CHILD = 'child';
}
