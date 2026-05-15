<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $user = User::query()->where('email', $dados['email'])->first();

        if (! $user instanceof User || ! Hash::check($dados['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais invalidas.'],
            ]);
        }

        $deviceName = $dados['device_name'] ?? $request->userAgent() ?? 'unknown';
        $token = $user->createToken($deviceName)->plainTextToken;

        return new JsonResponse([
            'token' => $token,
            'user' => UserResource::make($user)->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return new JsonResponse(status: 204);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make($user);
    }
}
