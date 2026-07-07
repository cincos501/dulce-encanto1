<?php

declare(strict_types=1);

namespace App\DTO;

class UserDTO
{
    public function __construct(
        public readonly string $full_name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly ?string $password = null,
        public readonly string $role = '',
        public readonly bool $is_active = true
    ) {}

    /**
     * Create a DTO from request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            full_name: (string) $data['full_name'],
            email: (string) $data['email'],
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            password: isset($data['password']) && $data['password'] !== '' ? (string) $data['password'] : null,
            role: (string) ($data['role'] ?? ''),
            is_active: filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
     * Convert DTO attributes to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arr = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ];

        if ($this->password !== null) {
            $arr['password'] = $this->password;
        }

        return $arr;
    }
}
