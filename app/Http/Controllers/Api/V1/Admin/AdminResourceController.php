<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Http\Resources\BookableResourceResource;
use App\Services\BookableResourceAdminService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin — Resources')]
class AdminResourceController extends Controller
{
    public function __construct(
        private readonly BookableResourceAdminService $resources,
    ) {}

    #[OA\Post(path: '/api/v1/admin/resources', security: [['bearerAuth' => []]], tags: ['Admin — Resources'])]
    #[OA\Response(response: 201, description: 'Created')]
    public function store(StoreResourceRequest $request): JsonResponse
    {
        $resource = $this->resources->create($request->validated());

        return (new BookableResourceResource($resource))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(path: '/api/v1/admin/resources/{resource}', security: [['bearerAuth' => []]], tags: ['Admin — Resources'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function update(UpdateResourceRequest $request, int $resource): JsonResponse
    {
        $model = $this->resources->update($resource, $request->validated());

        return response()->json(['data' => new BookableResourceResource($model)]);
    }
}
