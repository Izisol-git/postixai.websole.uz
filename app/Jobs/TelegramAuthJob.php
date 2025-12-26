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

            $php = '/opt/php83/bin/php';
            $artisan = base_path('artisan');
            $command = "nohup {$php} {$artisan} telegram:auth {$phone} {$userId} > /dev/null 2>&1 &";
            exec($command);
        
    }
}
