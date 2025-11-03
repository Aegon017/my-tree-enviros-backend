<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class PlanterPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Planter');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Planter');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Planter');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Planter');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Planter');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Planter');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Planter');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Planter');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Planter');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Planter');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Planter');
    }
}
