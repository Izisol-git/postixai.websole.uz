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
    $phone = $this->argument('phone');
    $userId = $this->argument('userId');

    Log::info("TelegramAuthCommand started for phone: {$phone}, userId: {$userId}");
    $this->info("Starting Telegram auth for {$phone}");

    $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");
    if (file_exists($sessionPath)) {
        if (is_dir($sessionPath)) {
            \Illuminate\Support\Facades\File::deleteDirectory($sessionPath);
        } else {
            unlink($sessionPath);
        }
        Log::info("Deleted existing session at {$sessionPath}");
        sleep(3);
    }

    if (!is_dir(dirname($sessionPath))) {
        mkdir(dirname($sessionPath), 0777, true);
        Log::info("Session directory created at " . dirname($sessionPath));
    }

    $settings = new Settings;
    $loggerSettings = (new LoggerSettings)->setType(Logger::FILE_LOGGER);
    $settings->setLogger($loggerSettings);
    $settings->setAppInfo(
        (new \danog\MadelineProto\Settings\AppInfo)
            ->setApiId(env('TELEGRAM_API_ID'))
            ->setApiHash(env('TELEGRAM_API_HASH'))
    );
    Log::info("MadelineProto settings prepared with API ID: " . env('TELEGRAM_API_ID'));

    $Madeline = new API($sessionPath, $settings);
    Log::info("MadelineProto API instance created for session {$sessionPath}");

    try {
        Log::info("Attempting phone login for {$phone}");
        $Madeline->phoneLogin($phone);
        $this->info("SMS code sent successfully to {$phone}");
        Log::info("SMS code sent successfully to {$phone}");
    } catch (\Exception $e) {
        $this->error("Error sending code: " . $e->getMessage());
        Log::error("Error sending code", ['exception' => $e]);
    } finally {
        $lockKey = "telegram_verify_lock_{$phone}_{$userId}";
        Cache::forget($lockKey);
        Log::info("Lock cleared: {$lockKey}");
    }
}

}
