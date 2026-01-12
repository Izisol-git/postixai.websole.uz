<?php

namespace App\Console\Commands;

use App\Models\UserPhone;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;

class TelegramLogoutCommand extends Command
{
    protected $signature = 'telegram:logout {userPhoneId}';
    protected $description = 'Logout Telegram for a given phone ID';

    protected function madeline($sessionPath)
    {
        $settings = new \danog\MadelineProto\Settings();

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
        $userPhoneId = $this->argument('userPhoneId');

        $userPhone = UserPhone::find($userPhoneId);
        Log::info("TelegramLogoutCommand started for phone ID {$userPhoneId}");


        if (!$userPhone) {
            $this->error("❌ UserPhone with ID {$userPhoneId} not found");
            return;
        }

        $sessionPath = $userPhone->session_path;

        if (!$sessionPath || !file_exists($sessionPath)) {
            Log::info("Session file for phone ID {$userPhoneId} not found. Cleaning DB record.");
            $userPhone->update(['session_path' => null, 'is_active' => false]);
            return;
        }


        try {
            $Madeline = $this->madeline($sessionPath);

            $Madeline->logOut();
            Log::info("Logged out from Telegram for phone ID {$userPhoneId}");

            if (File::exists($sessionPath)) {
                if (File::isDirectory($sessionPath)) {
                    File::deleteDirectory($sessionPath);
                } else {
                    File::delete($sessionPath); 
                }
            }

            $userPhone->update(['session_path' => null, 'is_active' => false]);


            Log::info("✅ Telegram logged out and session cleared for phone ID {$userPhoneId}");
        } catch (\Throwable $e) {
            $this->error("❌ Logout failed: " . $e->getMessage());

            $userPhone->update(['session_path' => null, 'is_active' => false]);
            if (File::exists($sessionPath)) {
                if (File::isDirectory($sessionPath)) {
                    File::deleteDirectory($sessionPath);
                } else {
                    File::delete($sessionPath); 
                }
            }
        }
    }
}
