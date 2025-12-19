<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function markRead(Request $request)
    {
        $data = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string',
            'all' => 'boolean',
        ]);

        $query = $request->user()->unreadNotifications();

        if (! empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        }

        $query->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function unreadCount(Request $request): array
    {
        return ['count' => $request->user()->unreadNotifications()->count()];
    }
}
