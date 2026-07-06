<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Issue an API token for an officer logging in by service number.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_number' => ['required', 'string'],
            'password'       => ['required', 'string'],
            'device_name'    => ['required', 'string'],  // e.g. "Samsung A15 - Ofc. Ceesay"
        ]);

        $user = User::where('service_number', $data['service_number'])->first();

        // Same generic message whether the account is missing or the password
        // is wrong — never reveal which service numbers exist.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            AuditLog::record('user.login_failed', null, [
                'service_number' => $data['service_number'],
            ]);

            throw ValidationException::withMessages([
                'service_number' => ['The provided credentials are incorrect.'],
            ]);
        }

        // A disabled account can never obtain a token.
        if (! $user->is_active) {
            AuditLog::record('user.login_blocked', $user, ['reason' => 'inactive']);

            throw ValidationException::withMessages([
                'service_number' => ['This account is not active.'],
            ]);
        }

        // One token per device; naming it after the device makes it easy to
        // revoke a single lost/stolen phone without logging everyone out.
        $token = $user->createToken($data['device_name'])->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        AuditLog::record('user.login', $user, ['device_name' => $data['device_name']]);

        return response()->json([
            'token' => $token,
            'user'  => $this->profile($user),
        ]);
    }

    /**
     * The current officer's profile. The app calls this on launch to confirm
     * its stored token is still valid.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    /**
     * Log out the current device only (revoke the token used for this request).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        AuditLog::record('user.logout', $request->user());

        return response()->json(['message' => 'Logged out.']);
    }

    private function profile(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'service_number' => $user->service_number,
            'rank'           => $user->rank,
            'station'        => $user->station,
            'roles'          => $user->getRoleNames(),
        ];
    }
}
