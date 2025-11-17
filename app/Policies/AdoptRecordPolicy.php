<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AdoptRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdoptRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdoptRecord');
    }

    public function view(AuthUser $authUser, AdoptRecord $adoptRecord): bool
    {
        return $authUser->can('View:AdoptRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdoptRecord');
    }

    public function update(AuthUser $authUser, AdoptRecord $adoptRecord): bool
    {
        return $authUser->can('Update:AdoptRecord');
    }

    public function delete(AuthUser $authUser, AdoptRecord $adoptRecord): bool
    {
        return $authUser->can('Delete:AdoptRecord');
    }

    public function restore(AuthUser $authUser, AdoptRecord $adoptRecord): bool
    {
        return $authUser->can('Restore:AdoptRecord');
    }

    public function forceDelete(AuthUser $authUser, AdoptRecord $adoptRecord): bool
    {
        return $authUser->can('ForceDelete:AdoptRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdoptRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdoptRecord');
    }

    public function replicate(AuthUser $authUser, AdoptRecord $adoptRecord): bool
    {
        return $authUser->can('Replicate:AdoptRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdoptRecord');
    }

}