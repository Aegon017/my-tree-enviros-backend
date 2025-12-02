<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function markRead(Request $request)
    {
        $data = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string',
            'all' => 'boolean',
        ]);

        $query = $request->user()->unreadNotifications();

        if (!empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        }

        $query->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function unreadCount(Request $request)
    {
        return ['count' => $request->user()->unreadNotifications()->count()];
    }
}
