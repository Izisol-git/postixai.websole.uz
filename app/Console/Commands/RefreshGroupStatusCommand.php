<?php

namespace App\Console\Commands;

use App\Models\UserPhone;
use App\Models\MessageGroup;
use danog\MadelineProto\API;
use App\Models\TelegramMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class RefreshGroupStatusCommand extends Command
{
    protected $signature = 'telegram:refresh-group {groupId}';
    protected $description = 'Refresh scheduled messages status for a group';

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
                    File::isDirectory($path)
                        ? File::deleteDirectory($path)
                        : File::delete($path);
                }

                $userPhone->update([
                    'session_path' => null,
                    'is_active' => false,
                ]);

                $group->messages()
                    ->where('status', 'pending')
                    ->update(['status' => 'failed']);
            }

            return Command::FAILURE;
        }

        // Faqat scheduled xabarlarni tekshiramiz
        $messages = $group->messages()->where('status', 'scheduled')->get();

        foreach ($messages as $message) {
            try {
                $messageId = $message->telegram_message_id;
                if (!$messageId) {
                    Log::warning("❌ Xabar telegram_message_id yo‘q: id={$message->id}");
                    continue;
                }

                $updates = $Madeline->messages->getScheduledHistory(['peer' => $message->peer]);
                $found = false;

                foreach ($updates['messages'] ?? [] as $m) {
                    if (($m['id'] ?? null) == $messageId) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    $message->update(['status' => 'scheduled']);
                } else {
                    Log::info("❌ Xabar Telegramda topilmadi, status o‘zgarmadi: id={$message->id}");
                }

            } catch (\Throwable $e) {
                Log::error("❌ Xabarni tekshirib bo‘lmadi", [
                    'peer' => $message->peer,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
