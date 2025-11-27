<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Campaign');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Campaign');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Campaign');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Campaign');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Campaign');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Campaign');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Campaign');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Campaign');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Campaign');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Campaign');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Campaign');
    }
}
