<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $name, string $email, string $password, UserRole $role = UserRole::User): array
    {
        $user = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    public function attempt(string $email, string $password): ?string
    {
        $user = $this->users->findByEmail($email);
        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return JWTAuth::fromUser($user);
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    public function refresh(): string
    {
        return JWTAuth::parseToken()->refresh();
    }

    public function logout(): void
    {
        JWTAuth::parseToken()->invalidate(true);
    }
}
