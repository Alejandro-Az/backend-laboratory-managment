<?php

namespace App\Policies;

use App\Models\Sample;
use App\Models\User;

class SamplePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('samples.view');
    }

    public function view(User $user, Sample $sample): bool
    {
        return $user->can('samples.view');
    }

    public function create(User $user): bool
    {
        return $user->can('samples.create');
    }

    public function update(User $user, Sample $sample): bool
    {
        return $user->can('samples.update');
    }

    public function delete(User $user, Sample $sample): bool
    {
        return $user->can('samples.delete');
    }

    public function restore(User $user, Sample $sample): bool
    {
        return $user->can('samples.restore');
    }

    public function changeStatus(User $user, Sample $sample): bool
    {
        return $user->can('samples.change_status');
    }

    public function changePriority(User $user, Sample $sample): bool
    {
        return $user->can('samples.change_priority');
    }

    public function addResult(User $user, Sample $sample): bool
    {
        return $user->can('samples.add_result');
    }

    public function viewEvents(User $user, Sample $sample): bool
    {
        return $user->can('samples.view_events');
    }
}
