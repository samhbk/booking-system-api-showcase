<?php

namespace App\Repositories\Contracts;

use App\Models\BookableResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookableResourceRepositoryInterface
{
    public function findById(int $id): ?BookableResource;

    public function findBySlug(string $slug): ?BookableResource;

    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): BookableResource;

    public function update(BookableResource $resource, array $attributes): BookableResource;

    public function activeForAvailabilityWarmup(): Collection;
}
