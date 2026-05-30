<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting: 10 attempts per 5 minutes per IP
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 300); // 5 minutes decay
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ], 403);
        }

        RateLimiter::clear($key);

        // Revoke old tokens then issue new one
        $user->tokens()->delete();
        $token = $user->createToken('api-token', ['*'], now()->addHours(8));

        AuditLog::record('login', 'users', $user->id);

        return response()->json([
            'message' => 'Login berhasil.',
            'token'   => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * POST /api/auth/register
     * Only admin can create accounts (or self-register aparatur_desa)
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'password'    => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role'        => 'required|in:aparatur_desa,bhabinkamtibmas,dinas,pusat,admin',
            'wilayah_id'  => 'nullable|uuid|exists:wilayah,id',
            'nik'         => 'nullable|string|max:20|unique:users,nik',
            'jabatan'     => 'nullable|string|max:100',
            'no_hp'       => 'nullable|string|max:20',
        ]);

        // Only admin can create non-aparatur_desa roles
        if ($request->role !== 'aparatur_desa' && !($request->user() && $request->user()->isAdmin())) {
            return response()->json([
                'message' => 'Hanya administrator yang dapat mendaftarkan role ini.',
            ], 403);
        }

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => $request->password,
            'role'       => $request->role,
            'wilayah_id' => $request->wilayah_id,
            'nik'        => $request->nik,
            'jabatan'    => $request->jabatan,
            'no_hp'      => $request->no_hp,
        ]);

        AuditLog::record('register', 'users', $user->id, null, $user->only(['name', 'email', 'role']));

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan login.',
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        AuditLog::record('logout', 'users', $request->user()->id);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('wilayah')));
    }

    /**
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'     => 'sometimes|string|max:100',
            'no_hp'    => 'sometimes|nullable|string|max:20',
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $old = $user->only(['name', 'no_hp']);

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->fill($request->only(['name', 'no_hp']));
        $user->save();

        AuditLog::record('update_profile', 'users', $user->id, $old, $user->only(['name', 'no_hp']));

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('api-token', ['*'], now()->addHours(8));

        return response()->json([
            'token'      => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
}
