<?php

namespace App\Console\Commands;

use App\Models\MessageGroup;
use App\Models\UserPhone;
use danog\MadelineProto\API;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SendTelegramMessage extends Command
{
    protected $signature = 'telegram:send-messages {groupId}';
    protected $description = 'Send telegram messages for given message group';

    public function handle()
    {
        $groupId = (int) $this->argument('groupId');

        $group = MessageGroup::find($groupId);

        if (!$group) {
            $this->error("MessageGroup topilmadi: id={$groupId}");
            Log::warning("MessageGroup topilmadi: id={$groupId}");
            return Command::FAILURE;
        }

        $userPhone = UserPhone::find($group->user_phone_id);

        if (!$userPhone || !$userPhone->session_path || !file_exists($userPhone->session_path)) {
            $this->error("Session topilmadi: user_phone_id={$group->user_phone_id}");
            Log::warning("❌ Session topilmadi: user_phone_id={$group->user_phone_id}");

            $group->messages()
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            return Command::FAILURE;
        }

        try {
            $Madeline = new API($userPhone->session_path);
            $Madeline->start();
        } catch (\Throwable $e) {
            Log::error("❌ MadelineProto start failed", [
                'user_phone_id' => $userPhone->id,
                'error' => $e->getMessage(),
            ]);

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

        // pending xabarlar
        $messages = $group->messages()
            ->where('status', 'pending')
            ->orderBy('send_at')
            ->get();

        foreach ($messages as $message) {
            try {
                // doim schedule qilamiz (hatto now ham bo‘lsa)
                $sendAt = $message->send_at?->isFuture() ? $message->send_at : now()->addSeconds(3);

                $payload = [
                    'peer' => $message->peer,
                    'message' => $message->message_text,
                    'parse_mode' => 'HTML',
                    'schedule_date' => $sendAt->timestamp,
                ];

                $response = $Madeline->messages->sendMessage($payload);
                $telegramMessageId = null;

                if (($response['_'] ?? null) === 'updateShortSentMessage') {
                    $status = 'sent';
                    $telegramMessageId = $response['id'] ?? null;
                } elseif (($response['_'] ?? null) === 'updates') {
                    foreach ($response['updates'] as $update) {
                        if (($update['_'] ?? null) === 'updateNewScheduledMessage') {
                            $status = 'scheduled';
                            $telegramMessageId = $update['message']['id'];
                            break;
                        }
                        if (($update['_'] ?? null) === 'updateNewMessage') {
                            $status = 'sent';
                            $telegramMessageId = $update['message']['id'];
                            break;
                        }
                    }
                }


                $message->update([
                    'status' => $status,
                    'sent_at' => now(),
                    'telegram_message_id' => $telegramMessageId,
                    'attempts' => 0,
                ]);


                $this->info("✅ Xabar yuborildi: {$message->peer}");
            } catch (\Throwable $e) {
                Log::error("❌ Xabar yuborilmadi", [
                    'peer' => $message->peer,
                    'error' => $e->getMessage(),
                ]);

                $message->increment('attempts');
                $message->update(['status' => 'failed']);
            }
        }

        $group->update(['status' => 'completed']);

        $this->info("🎉 Group yakunlandi: id={$groupId}");

        return Command::SUCCESS;
    }
}
