<?php

namespace App\Application\Services;

use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Support\Facades\Cache;
use App\Jobs\TelegramAuthJob;
use App\Jobs\TelegramVerifyJob;
use App\Jobs\TelegramLogoutJob;

class TelegramAuthService
{
    public function login(User $user, string $phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        $lockKey = "telegram_verify_lock_{$phone}_{$user->id}";
        if (!Cache::has($lockKey)) {
            Cache::put($lockKey, true, now()->addMinutes(10));
            TelegramAuthJob::dispatch($phone, $user->id)->onQueue('telegram');
        }
    }

    public function completedLogin(array $data)
    {
        $user = $data['user'];
        $phone = $data['phone'];
        $code = $data['code'];
        $password = $data['password'] ?? null;

        TelegramVerifyJob::dispatch($phone, $user->id, $code, $password)
            ->onQueue('telegram');
    }

    public function logout(User $user, string $phone): UserPhone
    {
        $userPhone = UserPhone::where('user_id', $user->id)
            ->where('phone', $phone)
            ->firstOrFail();

        $userPhone->state = 'logging_out';
        $userPhone->save();

        TelegramLogoutJob::dispatch($userPhone->id)
            ->onQueue('telegram');

        return $userPhone;
    }
}
