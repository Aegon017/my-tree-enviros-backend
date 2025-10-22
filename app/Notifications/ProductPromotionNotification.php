<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;

final class ProductPromotionNotification extends BaseFcmNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Product $product,
        protected string $promotionTitle,
        protected string $promotionImage
    ) {
        $this->title = $promotionTitle;
        $this->body = null; // Only title and image
        $this->image = $promotionImage;
        $this->path = '/products/' . $product->id;
        $this->emailSubject = $promotionTitle;

        $this->data = [
            'product_id' => (string) $product->id,
            'product_name' => $product->name,
            'type' => 'product_promotion',
            'timestamp' => now()->toISOString(),
        ];

        // Android specific config
        $this->androidConfig = [
            'sound' => 'promotional',
            'channel_id' => 'promotions',
            'priority' => 'default',
        ];

        // iOS specific config
        $this->iosConfig = [
            'sound' => 'promotional.wav',
            'badge' => 1,
            'category' => 'PROMOTION',
        ];

        // Web specific config
        $this->webConfig = [
            'icon' => url('/images/promotion-icon.png'),
            'requireInteraction' => true,
        ];
    }
}
