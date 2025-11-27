<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class SponsorRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SponsorRecord');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:SponsorRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SponsorRecord');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:SponsorRecord');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:SponsorRecord');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:SponsorRecord');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:SponsorRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SponsorRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SponsorRecord');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:SponsorRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SponsorRecord');
    }
}
