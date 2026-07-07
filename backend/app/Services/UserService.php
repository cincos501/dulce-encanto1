<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\UserDTO;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return $this->userRepository->all();
    }

    /**
     * Get paginated and filtered users.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $role = null, ?bool $isActive = null): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage, $search, $role, $isActive);
    }

    /**
     * Find a user or throw ModelNotFoundException.
     */
    public function findById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw (new ModelNotFoundException)->setModel(User::class, [$id]);
        }

        return $user;
    }

    /**
     * Create a new user and assign a role.
     */
    public function createUser(UserDTO $dto): User
    {
        $data = $dto->toArray();

        // Hash the password
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->create($data);

        // Assign Spatie role
        if ($dto->role !== '') {
            $roleObj = Role::where('name', $dto->role)->first();
            if ($roleObj) {
                $user->assignRole($roleObj);
            }
        }

        return $user;
    }

    /**
     * Update user details and roles.
     *
     * @throws \InvalidArgumentException
     */
    public function updateUser(User $user, UserDTO $dto): User
    {
        $currentUser = Auth::user();

        // Security rule: An admin cannot deactivate themselves
        if ($currentUser && $currentUser->id === $user->id && ! $dto->is_active) {
            throw new \InvalidArgumentException('No puedes desactivar tu propia cuenta de usuario.');
        }

        // Security rule: An admin cannot change their own role
        if ($currentUser && $currentUser->id === $user->id && $dto->role !== '' && ! $user->hasRole($dto->role)) {
            throw new \InvalidArgumentException('No puedes cambiar tu propio rol de usuario.');
        }

        $data = $dto->toArray();

        // Only hash and update password if it's set
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->update($user, $data);

        // Sync Spatie role
        if ($dto->role !== '') {
            $roleObj = Role::where('name', $dto->role)->first();
            if ($roleObj) {
                $user->syncRoles([$roleObj]);
            }
        }

        return $user;
    }

    /**
     * Deactivate a user.
     *
     * @throws \InvalidArgumentException
     */
    public function deleteUser(User $user): bool
    {
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->id === $user->id) {
            throw new \InvalidArgumentException('No puedes desactivar tu propia cuenta de usuario.');
        }

        return $this->userRepository->delete($user);
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(User $user, string $password): User
    {
        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }
}
