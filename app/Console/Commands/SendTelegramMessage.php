<?php

namespace App\Console\Commands;

use App\Models\MessageGroup;
use App\Models\UserPhone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Application\Services\MadelineService;

class SendTelegramMessage extends Command
{
    protected $signature = 'telegram:send-messages {groupId}';
    protected $description = 'Send telegram messages for given message group';

    protected MadelineService $madelineService;

    public function __construct(MadelineService $madelineService)
    {
        parent::__construct();
        $this->madelineService = $madelineService;
    }

    public function handle()
    {
        $groupId = (int) $this->argument('groupId');

        $group = MessageGroup::find($groupId);

        if (!$group) {
            $this->error("MessageGroup topilmadi: id={$groupId}");
            Log::warning("MessageGroup topilmadi: id={$groupId}");
            return self::FAILURE;
        }

        $userPhone = UserPhone::find($group->user_phone_id);

        if (!$userPhone) {
            $this->error("UserPhone topilmadi: id={$group->user_phone_id}");
            Log::warning("❌ UserPhone topilmadi: id={$group->user_phone_id}");
            $group->messages()->where('status', 'pending')->update(['status' => 'failed']);
            return self::FAILURE;
        }

        if (!$this->madelineService->validateAndStart($userPhone)) {
            $this->error("Session ishlamayapti: user_phone_id={$userPhone->id}");
            $group->messages()->where('status', 'pending')->update(['status' => 'failed']);
            return self::FAILURE;
        }

        $Madeline = $this->madelineService->getApi();

        $messages = $group->messages()
            ->where('status', 'pending')
            ->orderBy('send_at')
            ->get();

        foreach ($messages as $message) {
            try {
                $sendAt = $message->send_at?->isFuture() ? $message->send_at : now()->addSeconds(3);

                $payload = [
                    'peer' => $message->peer,
                    'message' => $message->message_text,
                    'parse_mode' => 'HTML',
                    'schedule_date' => $sendAt->timestamp,
                ];

                $response = $Madeline->messages->sendMessage($payload);
                $telegramMessageId = null;
                $status = 'sent';

                if (($response['_'] ?? null) === 'updateShortSentMessage') {
                    $telegramMessageId = $response['id'] ?? null;
                } elseif (($response['_'] ?? null) === 'updates') {
                    foreach ($response['updates'] as $update) {
                        if (($update['_'] ?? null) === 'updateNewScheduledMessage') {
                            $status = 'scheduled';
                            $telegramMessageId = $update['message']['id'] ?? null;
                            break;
                        }
                        if (($update['_'] ?? null) === 'updateNewMessage') {
                            $status = 'sent';
                            $telegramMessageId = $update['message']['id'] ?? null;
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

        return self::SUCCESS;
    }
}
