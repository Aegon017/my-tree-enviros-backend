<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class TreePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tree');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Tree');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tree');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Tree');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Tree');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Tree');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Tree');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tree');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tree');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Tree');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tree');
    }
}
