<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Get paginated and filtered users.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $role = null, ?bool $isActive = null): LengthAwarePaginator;

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): User;

    /**
     * Delete a user (soft/inactive or physical depending on rules - here we soft-deactivate).
     */
    public function delete(User $user): bool;
}
