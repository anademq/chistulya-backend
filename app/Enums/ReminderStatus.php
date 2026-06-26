<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
}
