<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Blogs\BlogCollection;
use App\Http\Resources\Api\V1\Blogs\BlogResource;
use App\Models\Blog;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogController extends Controller
{
    use ResponseHelpers;

    public function index(Request $request): JsonResponse
    {
        $query = Blog::query()
            ->with(['blogCategory']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('blog_category_id', (int) $request->input('category_id'));
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortOrder = mb_strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSortFields = ['created_at', 'title'];
        if (! in_array($sortBy, $allowedSortFields, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = min((int) $request->input('per_page', 12), 50);
        $blogs = $query->paginate($perPage);

        return $this->success(new BlogCollection($blogs));
    }

    public function show(string $identifier): JsonResponse
    {
        $blog = Blog::query()->with(['blogCategory'])
            ->when(is_numeric($identifier), fn ($q) => $q->where('id', $identifier))
            ->orWhere('slug', $identifier)
            ->first();

        if (! $blog) {
            return $this->notFound('Blog not found');
        }

        return $this->success([
            'blog' => new BlogResource($blog),
        ]);
    }
}
