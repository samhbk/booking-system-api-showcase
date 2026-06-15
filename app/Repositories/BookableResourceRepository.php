<?php

namespace App\Repositories;

use App\Models\BookableResource;
use App\Repositories\Contracts\BookableResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BookableResourceRepository implements BookableResourceRepositoryInterface
{
    public function findById(int $id): ?BookableResource
    {
        return BookableResource::query()->find($id);
    }

    public function findBySlug(string $slug): ?BookableResource
    {
        return BookableResource::query()->where('slug', $slug)->first();
    }

    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = BookableResource::query()->orderBy('name');
        if (! empty($filters['name'])) {
            $q->where('name', 'like', '%'.addcslashes((string) $filters['name'], '%_\\').'%');
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $q->where('is_active', (bool) $filters['is_active']);
        }

        return $q->paginate($perPage);
    }

    public function create(array $attributes): BookableResource
    {
        return BookableResource::query()->create($attributes);
    }

    public function update(BookableResource $resource, array $attributes): BookableResource
    {
        $resource->fill($attributes);
        $resource->save();

        return $resource->refresh();
    }

    public function activeForAvailabilityWarmup(): Collection
    {
        return BookableResource::query()->where('is_active', true)->orderBy('id')->get(['id', 'slug']);
    }
}
