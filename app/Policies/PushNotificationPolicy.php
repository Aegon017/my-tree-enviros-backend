<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class PushNotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PushNotification');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:PushNotification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PushNotification');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:PushNotification');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:PushNotification');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:PushNotification');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:PushNotification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PushNotification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PushNotification');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:PushNotification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PushNotification');
    }
}
