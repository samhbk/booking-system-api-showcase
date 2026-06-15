<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Tymon\JWTAuth\Facades\JWTAuth;

#[OA\Tag(name: 'Auth')]
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    #[OA\Post(path: '/api/v1/auth/register', summary: 'Register', tags: ['Auth'])]
    #[OA\Response(response: 201, description: 'Created')]
    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->register(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => (int) JWTAuth::factory()->getTTL() * 60,
            ],
        ], 201);
    }

    #[OA\Post(path: '/api/v1/auth/login', summary: 'Login', tags: ['Auth'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->auth->attempt(
            $request->validated('email'),
            $request->validated('password'),
        );

        if (! $token) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error' => 'invalid_credentials',
            ], 401);
        }

        $user = $this->auth->findUserByEmail($request->validated('email'));
        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error' => 'invalid_credentials',
            ], 401);
        }

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => (int) JWTAuth::factory()->getTTL() * 60,
            ],
        ]);
    }

    #[OA\Post(path: '/api/v1/auth/refresh', summary: 'Refresh JWT', tags: ['Auth'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function refresh(): JsonResponse
    {
        // jwt.refresh middleware already rotated the token before this action runs.
        return response()->json([
            'data' => [
                'access_token' => (string) JWTAuth::getToken(),
                'token_type' => 'bearer',
                'expires_in' => (int) JWTAuth::factory()->getTTL() * 60,
            ],
        ]);
    }

    #[OA\Get(path: '/api/v1/auth/me', summary: 'Current user', security: [['bearerAuth' => []]], tags: ['Auth'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    #[OA\Post(path: '/api/v1/auth/logout', summary: 'Logout', security: [['bearerAuth' => []]], tags: ['Auth'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Successfully logged out.']);
    }
}
