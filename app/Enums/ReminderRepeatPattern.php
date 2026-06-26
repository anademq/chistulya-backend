<?php

namespace App\Enums;

enum ReminderRepeatPattern: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
}
