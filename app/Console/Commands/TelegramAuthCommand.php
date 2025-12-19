<?php

namespace App\Console\Commands;

use App\Models\UserPhone;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use Illuminate\Console\Command;
use danog\MadelineProto\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use danog\MadelineProto\Settings\Logger as LoggerSettings;

class TelegramAuthCommand extends Command
{
    protected $signature = 'telegram:auth {phone} {userId}';
    protected $description = 'Send Telegram auth code to a phone number directly, without queue';

    public function handle()
    {
        Log::info("TelegramAuthCommand started");
        $phone = $this->argument('phone');
        $userId = $this->argument('userId');

        $this->info("Starting Telegram auth for {$phone}");

        $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");
        if (file_exists($sessionPath)) {
            if (is_dir($sessionPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($sessionPath);
            } else {
                unlink($sessionPath);
            }
            sleep(3);
        }
        if (!is_dir(dirname($sessionPath))) {
            mkdir(dirname($sessionPath), 0777, true);
        }


        $settings = new Settings;
                $loggerSettings = (new LoggerSettings)
                ->setType(Logger::FILE_LOGGER);
        $settings->setLogger($loggerSettings);
        $settings->setAppInfo(
            (new \danog\MadelineProto\Settings\AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'))
        );
        

        $Madeline = new API($sessionPath, $settings);

        try {
            $Madeline->phoneLogin($phone);
            $this->info("SMS code sent successfully to {$phone}");
        } catch (\Exception $e) {
            $this->error("Error sending code: " . $e->getMessage());
        }finally {
        // <<< Muhim: Har doim lock ni o'chiramiz >>>
        $lockKey = "telegram_verify_lock_{$this->argument('phone')}_{$this->argument('userId')}";
        Cache::forget($lockKey);
        // <<<
    }
    }
}
