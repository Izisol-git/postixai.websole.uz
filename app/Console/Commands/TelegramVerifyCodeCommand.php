<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\UserPhone;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use Illuminate\Console\Command;
use danog\MadelineProto\Settings;
use Illuminate\Support\Facades\Log;
use danog\MadelineProto\Settings\AppInfo;
use App\Application\Handlers\DefaultEventHandler;
use danog\MadelineProto\Settings\Logger as LoggerSettings;

class TelegramVerifyCodeCommand extends Command
{
    protected $signature = 'telegram:verify {phone} {userId} {code} {--password=}';
    protected $description = 'Verify Telegram login code for a given phone';

    protected function madeline($phone, $userId)
    {
        $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");

        if (!is_dir(dirname($sessionPath))) {
            mkdir(dirname($sessionPath), 0755, true);
        }

        $settings = new Settings();

        $appInfo = new AppInfo();
        $appInfo->setApiId((int) env('TELEGRAM_API_ID'));
        $appInfo->setApiHash(env('TELEGRAM_API_HASH'));
        $settings->setAppInfo($appInfo);

        $loggerSettings = (new LoggerSettings())
            ->setType(Logger::ERROR);

        $settings->setLogger($loggerSettings);

        return new API($sessionPath, $settings);
    }


    public function handle()
    {
        Log::info("TelegramVerifyCodeCommand started");

        $phone    = $this->argument('phone');
        $userId   = $this->argument('userId');
        $code     = $this->argument('code');
        $password = $this->option('password'); // optional (2FA uchun)

        $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");

        if (!file_exists($sessionPath)) {
            $this->error("❌ Session yo‘q, avval auth qiling!");
            return;
        }

        $Madeline = $this->madeline($phone, $userId);

        try {
            Log::info("Completing login for {$phone} with code {$code}");

            $authorization = $Madeline->completePhoneLogin($code);

            if ($authorization['_'] === 'account.noPassword') {
                throw new \Exception('2FA yoqilgan, lekin parol o‘rnatilmagan!');
            }

            if ($authorization['_'] === 'account.password') {
                if (!$password) {
                    $this->error("❌ Ushbu raqamda 2FA yoqilgan. Buyruqqa --password=PAROL qo‘shing");
                    return;
                }

                Log::info("2FA detected. Trying complete2falogin...");
                $authorization = $Madeline->complete2falogin($password);
            }

            if ($authorization['_'] === 'account.needSignup') {
                throw new \Exception("Bu raqam Telegram ro‘yxatidan o‘tmagan!");
            }


            UserPhone::updateOrCreate(
                [
                    'user_id' => $userId,
                    'phone'   => $phone,
                ],
                [
                    'session_path'      => $sessionPath,
                    // 'session_delete_at' => now()->addMinutes(15),
                    'is_active'         => true,
                ]
            );

            // $workerName = "telegram-worker-{$userId}";
            // exec("supervisorctl start {$workerName}");
            // Log::info("Supervisor worker started for {$phone} (id: {$userId})");
            if ($authorization) {
                // Session muvaffaqiyatli login qilindi
                $this->info("✅ {$phone} verified successfully");
            }
        } catch (\Throwable $e) {
            Log::error("VERIFY ERROR: " . $e->getMessage());
            $this->error("❌ VERIFY ERROR: " . $e->getMessage());
        }
    }
}
