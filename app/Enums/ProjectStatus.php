<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case ARCHIVED = 'archived';
}
