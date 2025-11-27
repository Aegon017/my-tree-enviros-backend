<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class SliderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Slider');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Slider');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Slider');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Slider');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Slider');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Slider');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Slider');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Slider');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Slider');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Slider');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Slider');
    }
}
