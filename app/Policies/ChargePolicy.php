<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ChargePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Charge');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Charge');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Charge');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Charge');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Charge');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Charge');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Charge');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Charge');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Charge');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Charge');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Charge');
    }
}
