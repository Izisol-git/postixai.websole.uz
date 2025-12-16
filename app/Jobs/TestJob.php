<?php

namespace App\Jobs;

use App\Models\UserPhone;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use danog\MadelineProto\Settings\Logger as LoggerSettings;



class TestJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sessionPath = UserPhone::find(7)->session_path;
        $settings = new Settings;
        $settings->getAppInfo()
            ->setApiId(env('TELEGRAM_API_ID'))
            ->setApiHash(env('TELEGRAM_API_HASH'));

        $loggerSettings = (new LoggerSettings)
            ->setType(Logger::FILE_LOGGER);

        $settings->setLogger($loggerSettings);

        $Madeline = new API($sessionPath, $settings);
        $Madeline->start();
        $self = $Madeline->getSelf();  
        $telegramUserId = $self['id'];
        Log::info("TEST JOB: Telegram User ID is {$telegramUserId}");
        
    }
}
