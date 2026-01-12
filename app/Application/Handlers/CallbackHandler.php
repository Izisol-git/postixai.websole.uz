<?php

namespace App\Application\Handlers;

use App\Models\User;
use Telegram\Bot\Api;
use App\Models\Catalog;
use App\Models\UserPhone;
use App\Jobs\TelegramLogoutJob;
use App\Jobs\TelegramVerifyJob;
use App\Application\Bot\BotContext;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use App\Application\Services\TelegramService;

class CallbackHandler
{
    protected Api $telegram;
    protected TelegramService $tgService;

    public function __construct(
        Api $telegram,
        TelegramService $tgService
    ) {
        $this->telegram = $telegram;
        $this->tgService = $tgService;
    }
    public function handle(BotContext $ctx): string
    {
        $update = $ctx->update;

        try {
            $callback = $update->getCallbackQuery();
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', ['exception' => $e]);
            return 'ok';
        }

        try {
            $ctx->telegram->answerCallbackQuery([
                'callback_query_id' => $callback->getId(),
                'text' => 'Tanlov qabul qilindi!',
                'show_alert' => false,
                'cache_time' => 5,
            ]);
        } catch (\Telegram\Bot\Exceptions\TelegramOtherException $e) {
            Log::warning('Callback query expired', ['exception' => $e]);
            return 'ok';
        }

        if (!$callback instanceof \Telegram\Bot\Objects\CallbackQuery) {
            Log::error('Callback query not valid', ['callback' => $callback]);
            return 'ok';
        }

        if ($callback && $callback->getMessage() && $callback->getMessage()->getChat()) {
            $data = $callback->getData();
            $chatId = $callback->getMessage()->getChat()->getId();
            $telegramUserId = $callback->get('from')->getId();
        }

        $user = User::where('telegram_id', $telegramUserId)->first();

            if (str_starts_with($data, 'catalog_page_')) {

                $page = (int) str_replace('catalog_page_', '', $data);

                $this->telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $callback->getMessage()->getMessageId(),
                    'text' => 'Iltimos, catalog tanlang:',
                    'reply_markup' => $this->tgService->buildCatalogKeyboard($user->id, $page)
                ]);

                return 'ok';
            }
            if (str_starts_with($data, 'phone_page_')) {

                $page = (int) str_replace('phone_page_', '', $data);

                $phones = $user->phones()->get();

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '📱 Telefon raqamini tanlang:',
                        'reply_markup' => $this->tgService->buildPhoneSelectKeyboard($phones, $page),
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'phone_delete_confirm_')) {

                $phoneId = (int) str_replace('phone_delete_confirm_', '', $data);

                $phone = UserPhone::where('id', $phoneId)
                    ->where('user_id', $user->id)
                    ->first();

                TelegramLogoutJob::dispatch($phoneId)->onQueue('telegram');
                sleep(3);

                $phones = $user->phones()->get();

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '🗑 Telefon o‘chirildi.',
                        'reply_markup' => $phones->isEmpty()
                            ? Keyboard::make()
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->row([
                                Keyboard::button([
                                    'text' => '📱 Telefon raqamini yuborish',
                                    'request_contact' => true,
                                ])
                            ])
                            : $this->tgService->buildPhoneSelectKeyboard($phones),
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'phone_delete_')) {

                $phoneId = (int) str_replace('phone_delete_', '', $data);

                $phone = UserPhone::where('id', $phoneId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$phone) {
                    return 'ok';
                }

                $confirmKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => '✅ Ha, o‘chirish',
                            'callback_data' => 'phone_delete_confirm_' . $phone->id,
                        ]),
                        Keyboard::inlineButton([
                            'text' => '❌ Yo‘q',
                            'callback_data' => 'cancel_auth',
                        ]),
                    ]);

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "⚠️ Raqamni o‘chirishni tasdiqlaysizmi?\n{$phone->phone}",
                        'reply_markup' => $confirmKeyboard,
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'phone_choose_')) {

                $phoneId = (int) str_replace('phone_choose_', '', $data);

                $phone = UserPhone::where('id', $phoneId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$phone) {
                    return 'ok';
                }

                $keyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => '🗑 O‘chirish',
                            'callback_data' => 'phone_delete_' . $phone->id,
                        ]),
                    ])
                    ->row([
                        Keyboard::inlineButton([
                            'text' => '❌ Bekor qilish',
                            'callback_data' => 'cancel_auth',
                        ]),
                    ]);

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "📱 Tanlangan raqam:\n{$phone->phone}\n\nQanday amal bajariladi?",
                        'reply_markup' => $keyboard,
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'groups_page_')) {

                $page = (int) str_replace('groups_page_', '', $data);

                $this->telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $callback->getMessage()->getMessageId(),
                    'text' => '📨 Xabarlar:',
                    'reply_markup' => $this->tgService->buildGroupKeyboard($user, $page)
                ]);

                return 'ok';
            }
            if ($data === 'catalog_create') {
                $user->state = 'creating_catalog';
                $user->save();
                $cancelKeyboard = Keyboard::make()->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Iltimos, yangi catalog nomini kiriting:",
                        'reply_markup' => $cancelKeyboard
                    ])
                );
            }
            if (str_starts_with($data, 'catalog_select_')) {

                $catalogId = (int) str_replace('catalog_select_', '', $data);
                $catalog = Catalog::find($catalogId);

                if (!$catalog) {
                    $this->tgService->tg(
                        fn() =>
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "⚠️ Catalog topilmadi."
                        ])
                    );
                    return 'ok';
                }

                $peers = json_decode($catalog->peers ?? '[]', true);

                // 📌 Text
                $text  = "📂 *Catalog:* {$catalog->title}\n\n";
                $text .= "👥 *Peerlar:*\n";

                if (empty($peers)) {
                    $text .= "— Peerlar yo‘q\n";
                } else {
                    foreach ($peers as $i => $peer) {
                        $text .= ($i + 1) . ". `{$peer}`\n";
                    }
                }

                $text .= "\n📌 Peerlar soni: " . count($peers);
                $text .= "\n\nQuyidagi amalni tanlang:";

                // 🔘 Keyboard
                $keyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => '▶️ Xabar yuborish',
                            'callback_data' => 'catalog_start_' . $catalog->id
                        ]),
                        Keyboard::inlineButton([
                            'text' => '🗑 Catalogni o‘chirish',
                            'callback_data' => 'catalog_delete_' . $catalog->id
                        ])
                    ])
                    ->row([
                        Keyboard::inlineButton([
                            'text' => '⬅️ Orqaga',
                            'callback_data' => 'catalog_page_1'
                        ])
                    ]);

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $text,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => $keyboard
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'catalog_delete_')) {

                $catalogId = (int) str_replace('catalog_delete_', '', $data);
                $catalog = \App\Models\Catalog::find($catalogId);

                if (!$catalog) {
                    $this->tgService->tg(
                        fn() =>
                        $this->telegram->answerCallbackQuery([
                            'callback_query_id' => $callback->getId(),
                            'text' => '⚠️ Catalog topilmadi',
                            'show_alert' => true
                        ])
                    );
                    return 'ok';
                }

                $keyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => '✅ Ha, o‘chirish',
                            'callback_data' => 'delete_catalog_confirm_' . $catalog->id
                        ]),
                        Keyboard::inlineButton([
                            'text' => '❌ Yo‘q',
                            'callback_data' => 'catalog_select_' . $catalog->id
                        ]),
                    ]);

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❗ *{$catalog->title}* catalogini o‘chirmoqchimisiz?\n\nBu amalni bekor qilib bo‘lmaydi.",
                        'parse_mode' => 'Markdown',
                        'reply_markup' => $keyboard
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'delete_catalog_confirm_')) {
                $catalogId = (int) str_replace('delete_catalog_confirm_', '', $data);
                $catalog = \App\Models\Catalog::find($catalogId);
                Log::info($data);
                Log::info($catalog);

                if ($catalog) {
                    Log::info('catalog');

                    $catalog->delete();
                }

                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->row([
                        Keyboard::button('Cataloglar'),
                    ]);

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '🗑 Catalog muvaffaqiyatli o‘chirildi.',
                        'reply_markup' => $keyboard
                    ])
                );

                return 'ok';
            }
            if ($data === 'catalog_delete_cancel') {

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => 'Bekor qilindi'
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'catalog_start_')) {

                $catalogId = str_replace('catalog_start_', '', $data);

                $phones = $user->phones()
                    ->where('is_active', true)
                    ->get()
                    ->toArray();

                $keyboard = $this->tgService->buildPhoneKeyboard($phones);

                $user->state = 'phone_selected';

                $json = json_encode([
                    'catalog_id'   => $catalogId,
                    'phone_id'     => null,
                    'message_text' => null,
                    'interval'     => null,
                    'loop_count'   => null,
                ], JSON_UNESCAPED_UNICODE);

                $user->value = $json;
                $user->save();

                $this->tgService->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => empty($phones)
                            ? "Telefonlar mavjud emas."
                            : "Telefonni tanlang:",
                        'reply_markup' => $keyboard
                    ])
                );

                return 'ok';
            }
            if (str_starts_with($data, 'group_select_')) {
                $groupId = (int) str_replace('group_select_', '', $data);
                $this->tgService->handleGroupSelect($groupId, $chatId);
            }
            if (str_starts_with($data, 'phone_select_')) {
                $phoneId = str_replace('phone_select_', '', $data);

                $phone = UserPhone::find($phoneId);

                if (!$phone) {
                    $this->tgService->tg(fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Telefon topilmadi."
                    ]));
                    return 'ok';
                }

                $user = $phone->user;

                $user->state = 'phone_selected';
                $phoneData = json_decode($user->value, true);
                $phoneData['phone_id'] = $phone->id;
                $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
                $user->save();
                $this->tgService->tg(fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon tanlandi. Iltimos, yuboriladigan xabar matnini kiriting:",
                    'reply_markup' => $this->tgService->cancelInlineKeyboard()
                ]));
                return 'ok';
            }
            if ($data === 'cancel_catalog') {
                $user->state = null;
                $user->value = null;
                $user->save();
                $activePhone = $user->phones()->where('is_active', true)->exists();
                $this->tgService->tg(fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Catalog tanlash bekor qilindi.',
                    'reply_markup' => $this->tgService->mainMenuWithHistoryKeyboard($activePhone)
                ]));
                return 'ok';
            }
            if ($data === 'skip_password' && $user) {

                $phone = $user->phones()
                    ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
                    ->latest()
                    ->first();


                if (!$phone) {
                    $this->tgService->tg(fn() =>
                    $this->telegram->answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => "Holat topilmadi",
                        'show_alert' => true,
                        // 'reply_markup' => $cancelKeyboard
                    ]));
                    return 'ok';
                }

                TelegramVerifyJob::dispatch(
                    $phone->phone,
                    $user->id,
                    $phone->code,
                    null
                )->onQueue('telegram');
                // $phoneNumber = $phone->phone;
                // $userId = $user->id;
                // $code = $phone->code;
                // $password = null;
                // $php     = '/opt/php83/bin/php';
                // $artisan = base_path('artisan');
                // if ($password) {
                //     $command = "nohup {$php} {$artisan} telegram:verify {$phoneNumber} {$userId} {$code} --password={$password} >/dev/null 2>&1 &";
                // } else {
                //     $command = "nohup {$php} {$artisan} telegram:verify {$phoneNumber} {$userId} {$code} >/dev/null 2>&1 &";
                // }
                // exec($command);

                $phone->update([
                    'code' => null,
                    'state' => 'loggin_process'
                ]);

                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callback->getId(),
                    'text' => "Password o'tkazib yuborildi",
                    'show_alert' => false,
                    'reply_markup' => $this->tgService->cancelInlineKeyboard()

                ]);
                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button('📱 Telefonlarim'),
                    ]);
                sleep(3);
                $this->tgService->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Tasdiqlash jarayoni boshlandi 🎉",
                    'reply_markup' => $this->tgService->mainMenuWithHistoryKeyboard(true)


                ]));
            }
            if ($data === 'cancel_auth' && $user) {
                return $this->tgService->cancelAuth($user, $chatId, $callback->getId());
            }
            return 'ok';

    }
}
