<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Exceptions\ApiException;

class TelegramController extends Controller
{
    public function __construct(protected TelegramAuthService $authService){}

    public function login(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $authUser = Auth::user();
        $user = $this->resolveUser($authUser, $request->user_id);

        $this->authService->login($user, $request->phone);

        return $this->success([], "Telegram auth started for {$user->name}");
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'code'     => 'required|string',
            'password' => 'nullable|string',
            'user_id'  => 'nullable|exists:users,id',
        ]);

        $authUser = Auth::user();
        $user = $this->resolveUser($authUser, $request->user_id);

        $userPhone = $this->authService->completedLogin([
            'user' => $user,
            'phone' => $request->phone,
            'code' => $request->code,
            'password' => $request->password
        ]);

        return $this->success($userPhone, "Telegram verification started for {$user->name}");
    }

    public function logout(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'user_id'  => 'nullable|exists:users,id',
        ]);

        $authUser = Auth::user();
        $user = $this->resolveUser($authUser, $request->user_id);

        $userPhone = $this->authService->logout($user, $request->phone);

        return $this->success($userPhone, "Telegram logout started for {$user->name}");
    }

    private function resolveUser(User $authUser, ?int $userId): User
    {
        if ($userId && $userId !== $authUser->id) {
            if ($authUser->role->name !== 'superadmin') {
                throw new ApiException('Forbidden: you cannot manage other users', 403);
            }
            $user = User::findOrFail($userId);
        } else {
            $user = $authUser;
        }

        if ($authUser->role->name === 'admin' && $user->department_id !== $authUser->department_id) {
            throw new ApiException('Forbidden: user not in your department', 403);
        }

        return $user;
    }
}
