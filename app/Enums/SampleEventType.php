<?php

namespace App\Enums;

enum SampleEventType: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case ANALYSIS_STARTED = 'analysis_started';
    case PRIORITY_CHANGED = 'priority_changed';
    case STATUS_CHANGED = 'status_changed';
    case RESULT_ADDED = 'result_added';
    case COMPLETED = 'completed';
    case DELETED = 'deleted';
    case RESTORED = 'restored';
}
