<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SupplyDTO;
use App\Models\Supply;
use App\Repositories\SupplyRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplyService
{
    public function __construct(
        protected SupplyRepositoryInterface $supplyRepository
    ) {}

    /**
     * Get paginated and filtered supplies.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        return $this->supplyRepository->paginate($perPage, $search, $onlyActive);
    }

    /**
     * Get all supplies.
     *
     * @return Collection<int, Supply>
     */
    public function all(bool $onlyActive = false): Collection
    {
        return $this->supplyRepository->all($onlyActive);
    }

    /**
     * Find a supply by ID or throw exception.
     */
    public function findById(int $id): Supply
    {
        $supply = $this->supplyRepository->findById($id);

        if ($supply === null) {
            throw (new ModelNotFoundException)->setModel(Supply::class, [$id]);
        }

        return $supply;
    }

    /**
     * Create a new supply.
     */
    public function create(SupplyDTO $dto): Supply
    {
        return DB::transaction(function () use ($dto): Supply {
            $supply = $this->supplyRepository->create($dto->toArray());

            $syncData = [];
            foreach ($dto->suppliers as $item) {
                $syncData[$item['supplier_id']] = ['purchase_price' => $item['purchase_price']];
            }
            $supply->suppliers()->sync($syncData);

            return $supply;
        });
    }

    /**
     * Update an existing supply.
     */
    public function update(int $id, SupplyDTO $dto): Supply
    {
        $supply = $this->findById($id);

        return DB::transaction(function () use ($supply, $dto): Supply {
            $this->supplyRepository->update($supply, $dto->toArray());

            $syncData = [];
            foreach ($dto->suppliers as $item) {
                $syncData[$item['supplier_id']] = ['purchase_price' => $item['purchase_price']];
            }
            $supply->suppliers()->sync($syncData);

            return $supply;
        });
    }

    /**
     * Toggle the active state of a supply.
     */
    public function toggleActive(int $id): Supply
    {
        $supply = $this->findById($id);

        return $this->supplyRepository->update($supply, [
            'is_active' => ! $supply->is_active,
        ]);
    }

    /**
     * Register a supply purchase from a supplier.
     */
    public function registerPurchase(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $supplierId = (int) $data['supplier_id'];
            foreach ($data['items'] as $item) {
                $supply = $this->findById((int) $item['supply_id']);

                // Increment stock using repository update
                $this->supplyRepository->update($supply, [
                    'stock' => $supply->stock + (float) $item['quantity']
                ]);

                // Sync/Update pivot price
                $supply->suppliers()->syncWithoutDetaching([
                    $supplierId => ['purchase_price' => (float) $item['purchase_price']]
                ]);
            }
        });
    }
}
