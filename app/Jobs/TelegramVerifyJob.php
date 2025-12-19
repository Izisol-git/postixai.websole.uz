<?php

namespace App\Jobs;

use App\Models\UserPhone;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TelegramVerifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $userId;
    public $code;
    public $password;

    public function __construct(string $phone, int $userId, string $code, ?string $password = null)
    {
        $this->phone = $phone;
        $this->userId = $userId;
        $this->code = $code;
        $this->password = $password;
    }

    protected function madeline($phone, $userId)
    {
        $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");

        if (!is_dir(dirname($sessionPath))) {
            mkdir(dirname($sessionPath), 0775, true);
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

    public function handle(): void
    {
        $phone    = $this->phone;
        $userId   = $this->userId;
        $code     = $this->code;
        $password = $this->password;


        $phoneNumber = $phone;
        $code = $code;
        $php     = '/opt/php83/bin/php';
        $artisan = base_path('artisan');
        if ($password) {
            $command = "nohup {$php} {$artisan} telegram:verify {$phoneNumber} {$userId} {$code} --password={$password} >/dev/null 2>&1 &";
        } else {
            $command = "nohup {$php} {$artisan} telegram:verify {$phoneNumber} {$userId} {$code} >/dev/null 2>&1 &";
        }
        exec($command);


        // $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");

        // if (!file_exists($sessionPath)) {
        //     Log::error("Session yo‘q, avval auth qiling! Phone: {$phone}");
        //     return;
        // }

        // $Madeline = $this->madeline($phone, $userId);

        // try {
        //     Log::info("Completing login for {$phone} with code {$code}");

        //     $authorization = $Madeline->completePhoneLogin($code);

        //     if ($authorization['_'] === 'account.noPassword') {
        //         throw new \Exception('2FA yoqilgan, lekin parol o‘rnatilmagan!');
        //     }

        //     if ($authorization['_'] === 'account.password') {
        //         if (!$password) {
        //             Log::error("Ushbu raqamda 2FA yoqilgan. Password kerak. Phone: {$phone}");
        //             return;
        //         }

        //         Log::info("2FA detected. Trying complete2falogin...");
        //         $authorization = $Madeline->complete2falogin($password);
        //     }

        //     if ($authorization['_'] === 'account.needSignup') {
        //         throw new \Exception("Bu raqam Telegram ro‘yxatidan o‘tmagan!");
        //     }
        //     $self = $Madeline->getSelf();  
        //     $telegramUserId = $self['id'];
        //     UserPhone::updateOrCreate(
        //         [
        //             'user_id' => $userId,
        //             'phone'   => $phone,

        //         ],
        //         [   
        //             'telegram_user_id' => $telegramUserId,
        //             'session_path' => $sessionPath,
        //             'is_active' => true
        //         ]
        //     );
        //     $self=$Madeline->getSelf();

        //     Log::info("✅ {$phone} verified successfully\n");


        // } catch (\Throwable $e) {
        //     Log::error("VERIFY ERROR ({$phone}): " . $e->getMessage());
        // }
    }
}
