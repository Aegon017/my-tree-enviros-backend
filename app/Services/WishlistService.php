<?php

namespace App\Services;

use App\Http\Resources\Api\V1\WishlistResource;
use App\Models\User;
use App\Repositories\WishlistRepository;
use Illuminate\Support\Facades\DB;

class WishlistService
{
    private $lastRemovedItem;
    public function __construct(private WishlistRepository $repo) {}

    public function get(User $user)
    {
        $wishlist = $this->repo->getUserWishlist($user->id);

        return [
            'wishlist' => new WishlistResource($wishlist),
        ];
    }

    public function add(User $user, array $data)
    {
        $wishlist = $this->repo->getUserWishlist($user->id);

        if ($this->repo->exists($wishlist, $data['product_id'], $data['product_variant_id'] ?? null)) {
            return ['success' => false, 'message' => 'Already in wishlist'];
        }

        DB::transaction(fn() => $this->repo->addItem($wishlist, $data));

        return $this->get($user);
    }

    public function remove(User $user, int $itemId)
    {
        $wishlist = $this->repo->getUserWishlist($user->id);
        $item = $this->repo->findItem($wishlist, $itemId);

        if (!$item) return ['success' => false, 'message' => 'Not found'];

        $this->lastRemovedItem = $item;

        DB::transaction(fn() => $this->repo->deleteItem($item));

        return $this->get($user);
    }

    public function getLastRemovedItem()
    {
        return $this->lastRemovedItem;
    }

    public function clear(User $user)
    {
        $wishlist = $this->repo->getUserWishlist($user->id);
        DB::transaction(fn() => $this->repo->clear($wishlist));
        return $this->get($user);
    }
}
