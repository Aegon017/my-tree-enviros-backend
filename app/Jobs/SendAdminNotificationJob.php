<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\User;
use App\Notifications\BaseAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendAdminNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $id) {}

    public function handle(): void
    {
        $a = AdminNotification::find($this->id);

        $query = User::query();

        if ($a->target['type'] === 'users') {
            $query->whereIn('id', $a->target['users']);
        }

        if ($a->target['type'] === 'roles') {
            $query->whereIn('role', $a->target['roles']);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $user->notify(
                new BaseAppNotification(
                    $a->title,
                    $a->body,
                    [
                        'admin_notification_id' => $a->id,
                        'link' => $a->link,
                    ],
                    $a->channels
                )
            );
        }
    }
}
