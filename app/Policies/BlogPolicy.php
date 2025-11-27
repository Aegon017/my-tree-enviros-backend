<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class BlogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Blog');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Blog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Blog');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Blog');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Blog');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Blog');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Blog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Blog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Blog');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Blog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Blog');
    }
}
