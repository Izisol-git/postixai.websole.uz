<?php

namespace App\Console\Commands;

use App\Models\MessageGroup;
use danog\MadelineProto\API;
use App\Models\UserPhone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class CleanupScheduledCommand extends Command
{
    protected $signature = 'telegram:cleanup-group {groupId}';
    protected $description = 'Delete scheduled/failed messages from Telegram and mark as canceled in DB';

    public function handle()
    {
        $groupId = (int) $this->argument('groupId');

        $group = MessageGroup::with('messages')->find($groupId);
        if (!$group) {
            Log::warning("❌ Guruh topilmadi: id={$groupId}");
            return Command::FAILURE;
        }

        $userPhone = UserPhone::find($group->user_phone_id);
        if (!$userPhone || !$userPhone->session_path) {
            Log::warning("❌ Session topilmadi: user_phone_id={$group->user_phone_id}");
            return Command::FAILURE;
        }
        
        try {
            $Madeline = new API($userPhone->session_path);
            $Madeline->start();
        } catch (\Throwable $e) {
            Log::error("❌ MadelineProto start failed: user_phone_id={$userPhone->id}, error={$e->getMessage()}");

            $msg = $e->getMessage();
            $shouldReset =
                str_contains($msg, 'AUTH_KEY_UNREGISTERED') ||
                str_contains($msg, 'SESSION_REVOKED') ||
                str_contains($msg, 'AUTH_KEY_INVALID');

            if ($shouldReset) {
                Log::warning("🔄 Session RESET qilinmoqda: user_phone_id={$userPhone->id}");
                $path = $userPhone->session_path;
                if (File::exists($path)) {
                    File::isDirectory($path) ? File::deleteDirectory($path) : File::delete($path);
                }

                $userPhone->update(['session_path' => null, 'is_active' => false]);
                $group->messages()->where('status', 'pending')->update(['status' => 'failed']);
            }

            return Command::FAILURE;
        }

        $messages = $group->messages()->whereIn('status', ['scheduled', 'failed'])->get();

        foreach ($messages as $message) {
            try {
                if ($message->telegram_message_id) {
                    $Madeline->messages->deleteScheduledMessages([
                        'peer' => $message->peer,
                        'id' => [$message->telegram_message_id],
                    ]);
                }

                $message->update(['status' => 'canceled']);
            } catch (\Throwable $e) {
                Log::error("Xabarni o‘chirib bo‘lmadi: id={$message->id}, error={$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
