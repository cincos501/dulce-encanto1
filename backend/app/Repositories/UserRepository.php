<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::with('roles')->orderBy('full_name')->get();
    }

    /**
     * Get paginated and filtered users.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $role = null, ?bool $isActive = null): LengthAwarePaginator
    {
        $query = User::with('roles');

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role !== null && $role !== '') {
            $query->role($role);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->orderBy('full_name')->paginate($perPage);
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::with('roles')->where('email', $email)->first();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    /**
     * Delete a user. We deactivate it rather than hard delete it.
     */
    public function delete(User $user): bool
    {
        $user->is_active = false;

        return $user->save();
    }
}
