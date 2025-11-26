<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Services\CampaignService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CampaignController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly CampaignService $campaignService) {}

    public function index(Request $request): JsonResponse
    {
        $collection = $this->campaignService->paginate($request);

        return $this->success($collection->toArray($request));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $campaign = $this->campaignService->findByIdOrSlug($id);

        if ($campaign === null) {
            return $this->notFound('Campaign not found');
        }

        return $this->success([
            'campaign' => new CampaignResource($campaign),
        ]);
    }
}
