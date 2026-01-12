<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\Role;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Exception;

class VerifyPhoneWithUserCommand extends Command
{
    protected $signature = 'telegram:userWithPhone {phone} {code} {userId} {--department=} {--password=}';
    protected $description = 'Verify phone with MadelineProto, get telegram user info and create/update local User + UserPhone';

    protected function findSessionPath(string $phone, string $userId): ?string
    {
        $path = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");
        return file_exists($path) ? $path : null;
    }


    protected function madeline($sessionPath)
    {
        if (!is_dir(dirname($sessionPath))) {
            mkdir(dirname($sessionPath), 0755, true);
        }

        $settings = new Settings();
        $appInfo = new AppInfo();
        $appInfo->setApiId((int) env('TELEGRAM_API_ID'));
        $appInfo->setApiHash(env('TELEGRAM_API_HASH'));
        $settings->setAppInfo($appInfo);

        $loggerSettings = (new LoggerSettings())->setType(Logger::ERROR);
        $settings->setLogger($loggerSettings);

        return new API($sessionPath, $settings);
    }

    public function handle()
    {
        $phone = $this->argument('phone');
        $code  = $this->argument('code');
        $userId = $this->argument('userId');
        $department = $this->option('department');
        $password   = $this->option('password');

        $sessionPath = $this->findSessionPath($phone, $userId);

        Log::info("starting VerifyPhoneWithUserCommand for phone {$phone}");
        if (!$sessionPath) {
            $this->error("❌ Session fayli topilmadi uchun: {$phone}. session papkasini tekshiring.");
            Log::error("VerifyPhoneWithUserCommand: session not found for phone {$phone}");
            return 1;
        }

        try {
            Log::info("VerifyPhoneWithUserCommand: completing login for {$phone}");

            $Madeline = $this->madeline($sessionPath);

            $authorization = $Madeline->completePhoneLogin($code);

            if ($authorization['_'] === 'account.noPassword') {
                $this->error('2FA yoqilgan, lekin parol o‘rnatilmagan!');
                return 1;
            }


            if ($authorization['_'] === 'account.password') {
                if (!$password) {
                    $this->error("2FA yoqilgan. --password=PAROL qo‘shing");
                    return 1;
                }

                $authorization = $Madeline->complete2falogin($password);
            }


            if ($authorization['_'] === 'account.needSignup') {
                $this->error("Bu raqam Telegram ro‘yxatidan o‘tmagan!");
                return 1;
            }

            $self = $Madeline->getSelf();
            $telegramUserId = $self['id'] ?? null;
            $name = trim(($self['first_name'] ?? '') . ' ' . ($self['last_name'] ?? '')) ?: ($self['username'] ?? $phone);

            if (!$telegramUserId) {
                throw new Exception('Telegram user id olinmadi');
            }

            DB::transaction(function () use ($phone, $sessionPath, $telegramUserId, $name, $department) {
                $user = User::where('telegram_id', $telegramUserId)->first();
                if (!$user) {
                    $role = Role::where('name', 'user')->first();
                    $user = User::create([
                        'name' => $name,
                        'telegram_id' => $telegramUserId,
                        'department_id' => $department,
                        'role_id' => $role ? $role->id : null,
                        'oferta_read' => false,
                    ]);
                }

                UserPhone::updateOrCreate(
                    ['user_id' => $user->id, 'phone' => $phone],
                    [
                        'telegram_user_id' => $telegramUserId,
                        'session_path' => $sessionPath,
                        'is_active' => true,
                    ]
                );
            });

            $this->info("✅ {$phone} verified and user created/updated");
            return 0;
        } catch (\Throwable $e) {
            Log::error("VerifyPhoneWithUserCommand ERROR ({$phone}): " . $e->getMessage());
            $this->error("❌ VERIFY ERROR: " . $e->getMessage());
            return 1;
        }
    }
}
