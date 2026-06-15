<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookableResourceResource;
use App\Repositories\Contracts\BookableResourceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Resources')]
class ResourceController extends Controller
{
    public function __construct(
        private readonly BookableResourceRepositoryInterface $resources,
    ) {}

    #[OA\Get(path: '/api/v1/resources', summary: 'List resources', tags: ['Resources'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 15);
        unset($filters['per_page']);

        $listFilters = [
            'name' => $filters['q'] ?? null,
            'is_active' => $filters['is_active'] ?? null,
        ];

        $paginator = $this->resources->paginateFiltered($listFilters, $perPage);

        return BookableResourceResource::collection($paginator)
            ->additional(['meta' => ['pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]]])
            ->response();
    }

    #[OA\Get(path: '/api/v1/resources/{resource}', summary: 'Get resource', tags: ['Resources'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function show(int $resource): JsonResponse
    {
        $model = $this->resources->findById($resource);
        if (! $model) {
            return response()->json(['message' => 'Not found.', 'error' => 'not_found'], 404);
        }

        return response()->json(['data' => new BookableResourceResource($model)]);
    }
}
