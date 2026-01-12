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
       
    }
}
