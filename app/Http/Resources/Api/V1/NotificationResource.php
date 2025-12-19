<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->data['title'] ?? 'Notification',
            'message' => $this->data['body'] ?? '',
            'image' => $this->data['data']['image'] ?? null,
            'link' => $this->data['data']['link'] ?? null,
            'data' => $this->data['data'] ?? [],
            'read_at' => $this->read_at,
            'created_at' => $this->created_at->toDateTimeString(),
            'send_to' => 'user',
            'user_ids' => null,
        ];
    }
}
