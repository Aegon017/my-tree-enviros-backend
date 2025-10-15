<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Location;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class LocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Location');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Location');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Location');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Location');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Location');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Location');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Location');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Location');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Location');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Location');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Location');
    }
}
