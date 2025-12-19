<?php

namespace App\Jobs;

use App\Models\MessageGroup;
use App\Models\UserPhone;
use danog\MadelineProto\API;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger as LoggerSettings;
use danog\MadelineProto\Logger;

class SendTelegramMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    protected int $groupId;

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    public function handle()
    {

        Log::info('work');
        $php = '/opt/php83/bin/php';
            $artisan = base_path('artisan');
            $command = "nohup {$php} {$artisan} telegram:send-messages {$this->groupId} > /dev/null 2>&1 &";
            exec($command);
//         $group = MessageGroup::find($this->groupId);

//         if (!$group) {
//             Log::warning("MessageGroup topilmadi: id={$this->groupId}");
//             return;
//         }

//         $userPhone = UserPhone::find($group->user_phone_id);

//         if (!$userPhone || !file_exists($userPhone->session_path)) {
//             Log::warning("❌ Session topilmadi: user_phone_id={$group->user_phone_id}");
//             $group->messages()->where('status', 'pending')->update(['status' => 'failed']);
//             return;
//         }

//         try {
//             $Madeline = new API($userPhone->session_path);
//             $Madeline->start();
//         } catch (\Throwable $e) {

//             Log::error("❌ MadelineProto start failed for user_phone_id={$group->user_phone_id}", [
//                 'error' => $e->getMessage(),
//             ]);

//             $msg = $e->getMessage();

//             $shouldReset =
//                 str_contains($msg, 'AUTH_KEY_UNREGISTERED') ||
//                 str_contains($msg, 'SESSION_REVOKED') ||
//                 str_contains($msg, 'AUTH_KEY_INVALID');

//             if ($shouldReset) {
//                 Log::warning("🔄 Session RESET qilinmoqda: user_phone_id={$userPhone->id}");

//                 $path = $userPhone->session_path;

//                 if (File::exists($path)) {
//                 if (File::isDirectory($path)) {
//                     File::deleteDirectory($path);
//                 } else {
//                     File::delete($path); // unlink o‘rniga Laravel helper
//                 }
//                 }

//                 $userPhone->update(['session_path' => null],'is_active', false);

//                 $group->messages()->where('status', 'pending')
//                     ->update(['status' => 'failed']);
//             }

//             return;
//         }

//         // Pending xabarlarni olish, send_at bo'yicha saralash
//         $messages = $group->messages()
//             ->where('status', 'pending')
//             ->orderBy('send_at')
//             ->get();

//         foreach ($messages as $msg) {
//     try {
//         $response = $Madeline->messages->sendMessage([
//             'peer' => $msg->peer,
//             'message' => $msg->message_text,
//             'parse_mode' => 'HTML',
//             'schedule_date' => $msg->send_at?->timestamp ?? null, 
//         ]);
//         Log::info($msg->send_at?->timestamp ?? null);
//         $scheduledMessageId = $response['updates'][0]['message']['id'] ?? null;

//         $msg->update([
//             'status' => 'sent',
//             'sent_at' => now(),
//             'attempts' => 0,
//             'telegram_message_id' => $scheduledMessageId,
//         ]);

//         Log::info("✅ Xabar yuborildi: peer={$msg->peer}, message_id={$msg->id}");
//     } catch (\Throwable $e) {
//         Log::error("❌ Xabar yuborilmadi: peer={$msg->peer}", [
//             'error' => $e->getMessage(),
//         ]);

//         $msg->increment('attempts');
//         $msg->update(['status' => 'failed']);
//     }
// }
//         $group->update(['status' => 'completed']);
    }
}
