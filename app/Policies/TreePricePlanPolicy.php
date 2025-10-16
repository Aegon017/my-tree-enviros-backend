<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TreePricePlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class TreePricePlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TreePricePlan');
    }

    public function view(AuthUser $authUser, TreePricePlan $treePricePlan): bool
    {
        return $authUser->can('View:TreePricePlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TreePricePlan');
    }

    public function update(AuthUser $authUser, TreePricePlan $treePricePlan): bool
    {
        return $authUser->can('Update:TreePricePlan');
    }

    public function delete(AuthUser $authUser, TreePricePlan $treePricePlan): bool
    {
        return $authUser->can('Delete:TreePricePlan');
    }

    public function restore(AuthUser $authUser, TreePricePlan $treePricePlan): bool
    {
        return $authUser->can('Restore:TreePricePlan');
    }

    public function forceDelete(AuthUser $authUser, TreePricePlan $treePricePlan): bool
    {
        return $authUser->can('ForceDelete:TreePricePlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TreePricePlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TreePricePlan');
    }

    public function replicate(AuthUser $authUser, TreePricePlan $treePricePlan): bool
    {
        return $authUser->can('Replicate:TreePricePlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TreePricePlan');
    }

}