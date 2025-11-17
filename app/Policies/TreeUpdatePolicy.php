<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TreeUpdate;
use Illuminate\Auth\Access\HandlesAuthorization;

class TreeUpdatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TreeUpdate');
    }

    public function view(AuthUser $authUser, TreeUpdate $treeUpdate): bool
    {
        return $authUser->can('View:TreeUpdate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TreeUpdate');
    }

    public function update(AuthUser $authUser, TreeUpdate $treeUpdate): bool
    {
        return $authUser->can('Update:TreeUpdate');
    }

    public function delete(AuthUser $authUser, TreeUpdate $treeUpdate): bool
    {
        return $authUser->can('Delete:TreeUpdate');
    }

    public function restore(AuthUser $authUser, TreeUpdate $treeUpdate): bool
    {
        return $authUser->can('Restore:TreeUpdate');
    }

    public function forceDelete(AuthUser $authUser, TreeUpdate $treeUpdate): bool
    {
        return $authUser->can('ForceDelete:TreeUpdate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TreeUpdate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TreeUpdate');
    }

    public function replicate(AuthUser $authUser, TreeUpdate $treeUpdate): bool
    {
        return $authUser->can('Replicate:TreeUpdate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TreeUpdate');
    }

}