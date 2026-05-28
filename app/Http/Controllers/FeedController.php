<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityFeedResource;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        protected FeedService $feedService
    ) {}

    /**
     * Obtener feed de actividad del usuario autenticado.
     */
    public function myFeed(Request $request): JsonResponse
    {
        $feed = $this->feedService->getFeed(auth()->id(), $request->all());

        return response()->json([
            'success' => true,
            'data' => ActivityFeedResource::collection($feed),
            'meta' => [
                'current_page' => $feed->currentPage(),
                'last_page' => $feed->lastPage(),
                'per_page' => $feed->perPage(),
                'total' => $feed->total(),
            ],
        ]);
    }

    /**
     * Obtener feed global de actividad.
     */
    public function globalFeed(Request $request): JsonResponse
    {
        $feed = $this->feedService->getGlobalFeed($request->all());

        return response()->json([
            'success' => true,
            'data' => ActivityFeedResource::collection($feed),
            'meta' => [
                'current_page' => $feed->currentPage(),
                'last_page' => $feed->lastPage(),
                'per_page' => $feed->perPage(),
                'total' => $feed->total(),
            ],
        ]);
    }
}
