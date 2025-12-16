<?php

namespace App\Jobs;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TelegramAuthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, int $userId)
    {
        $this->phone = $phone;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $phone = $this->phone;
        $userId = $this->userId;

        Log::info("TelegramAuthJob started for {$phone}, user {$userId}");

        $sessionPath = storage_path("app/sessions/{$phone}_user_{$userId}.madeline");

        // Удаляем старую сессию
        if (file_exists($sessionPath)) {
            if (is_dir($sessionPath)) {
                File::deleteDirectory($sessionPath);
            } else {
                unlink($sessionPath);
            }
            sleep(3);
        }

        if (!is_dir(dirname($sessionPath))) {
            mkdir(dirname($sessionPath), 0777, true);
        }

        $settings = new Settings;
        $settings->setAppInfo(
            (new \danog\MadelineProto\Settings\AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'))
        );

        try {
            $Madeline = new API($sessionPath, $settings);
            $Madeline->phoneLogin($phone);
            Log::info("SMS code sent successfully to {$phone}");
        } catch (\Exception $e) {
            Log::error("TelegramAuthJob error for {$phone}: " . $e->getMessage());
        }
    }
}
