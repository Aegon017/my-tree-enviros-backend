<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TreeInstance;
use Illuminate\Auth\Access\HandlesAuthorization;

class TreeInstancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TreeInstance');
    }

    public function view(AuthUser $authUser, TreeInstance $treeInstance): bool
    {
        return $authUser->can('View:TreeInstance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TreeInstance');
    }

    public function update(AuthUser $authUser, TreeInstance $treeInstance): bool
    {
        return $authUser->can('Update:TreeInstance');
    }

    public function delete(AuthUser $authUser, TreeInstance $treeInstance): bool
    {
        return $authUser->can('Delete:TreeInstance');
    }

    public function restore(AuthUser $authUser, TreeInstance $treeInstance): bool
    {
        return $authUser->can('Restore:TreeInstance');
    }

    public function forceDelete(AuthUser $authUser, TreeInstance $treeInstance): bool
    {
        return $authUser->can('ForceDelete:TreeInstance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TreeInstance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TreeInstance');
    }

    public function replicate(AuthUser $authUser, TreeInstance $treeInstance): bool
    {
        return $authUser->can('Replicate:TreeInstance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TreeInstance');
    }

}