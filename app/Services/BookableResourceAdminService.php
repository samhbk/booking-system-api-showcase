<?php

namespace App\Services;

use App\Models\BookableResource;
use App\Repositories\Contracts\BookableResourceRepositoryInterface;
use Illuminate\Support\Str;

class BookableResourceAdminService
{
    public function __construct(
        private readonly BookableResourceRepositoryInterface $resources,
    ) {}

    public function create(array $data): BookableResource
    {
        $name = (string) $data['name'];
        $slug = isset($data['slug'])
            ? $this->uniqueSlug(Str::slug((string) $data['slug']))
            : $this->uniqueSlug(Str::slug($name));

        return $this->resources->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'slot_duration_minutes' => (int) ($data['slot_duration_minutes'] ?? 60),
            'capacity' => (int) ($data['capacity'] ?? 1),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function update(int $id, array $data): BookableResource
    {
        $model = $this->resources->findById($id);
        if (! $model) {
            abort(404, 'Not found.');
        }

        $attrs = [];
        if (array_key_exists('name', $data)) {
            $attrs['name'] = (string) $data['name'];
        }
        if (array_key_exists('slug', $data)) {
            $attrs['slug'] = $this->uniqueSlug(Str::slug((string) $data['slug']), $id);
        } elseif (array_key_exists('name', $data)) {
            $attrs['slug'] = $this->uniqueSlug(Str::slug($attrs['name']), $id);
        }
        foreach (['description', 'timezone', 'slot_duration_minutes', 'capacity', 'is_active'] as $k) {
            if (array_key_exists($k, $data)) {
                $attrs[$k] = $data[$k];
            }
        }

        return $this->resources->update($model, $attrs);
    }

    private function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base;
        $n = 0;
        while (true) {
            $found = $this->resources->findBySlug($slug);
            if (! $found || ($exceptId !== null && (int) $found->id === $exceptId)) {
                return $slug;
            }
            $n++;
            $slug = $base.'-'.$n;
        }
    }
}
