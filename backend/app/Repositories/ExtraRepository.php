<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Extra;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExtraRepository implements ExtraRepositoryInterface
{
    /**
     * Get all extras.
     *
     * @return Collection<int, Extra>
     */
    public function all(): Collection
    {
        return Extra::orderBy('name')->get();
    }

    /**
     * Get paginated and filtered extras.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $query = Extra::query();

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find an extra by ID.
     */
    public function findById(int $id): ?Extra
    {
        return Extra::find($id);
    }

    /**
     * Create a new extra.
     */
    public function create(array $data): Extra
    {
        return Extra::create($data);
    }

    /**
     * Update an existing extra.
     */
    public function update(Extra $extra, array $data): Extra
    {
        $extra->update($data);

        return $extra;
    }

    /**
     * Delete an extra.
     */
    public function delete(Extra $extra): bool
    {
        return (bool) $extra->delete();
    }
}
