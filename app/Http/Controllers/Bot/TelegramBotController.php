<?php

namespace App\Http\Controllers\Bot;

use App\Models\User;
use Telegram\Bot\Api;
use App\Models\Catalog;
use App\Models\UserPhone;
use App\Models\MessageGroup;
use App\Jobs\TelegramAuthJob;
use App\Jobs\TelegramLogoutJob;
use App\Jobs\TelegramVerifyJob;
use App\Models\TelegramMessage;
use App\Jobs\CleanupScheduledJob;
use App\Jobs\SendTelegramMessages;
use App\Jobs\RefreshGroupStatusJob;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions\TelegramResponseException;

class TelegramBotController extends Controller
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    public function webhook()
    {
        // return "ok";
        $update = $this->telegram->getWebhookUpdate();



        if ($update->getCallbackQuery()) {

            // $callback = $update->getCallbackQuery();
            try {
                $callback = $update->getCallbackQuery();
            } catch (\Exception $e) {
                Log::error('Telegram webhook error', ['exception' => $e]);
                return response('ok', 200);
            }
            try {
                $this->telegram->answerCallbackQuery([
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
                    'reply_markup' => $this->buildCatalogKeyboard($user->id, $page)
                ]);

                return 'ok';
            }
            if (str_starts_with($data, 'phone_page_')) {

                $page = (int) str_replace('phone_page_', '', $data);

                $phones = $user->phones()->get();

                $this->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '📱 Telefon raqamini tanlang:',
                        'reply_markup' => $this->buildPhoneSelectKeyboard($phones, $page),
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

                $this->tg(
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
                            : $this->buildPhoneSelectKeyboard($phones),
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

                $this->tg(
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

                $this->tg(
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
                    'reply_markup' => $this->buildGroupKeyboard($user, $page)
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

                $this->tg(
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
                    $this->tg(
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

                $this->tg(
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
                    $this->tg(
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

                $this->tg(
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

                $this->tg(
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

                $this->tg(
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

                $keyboard = $this->buildPhoneKeyboard($phones);

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

                $this->tg(
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
                $this->handleGroupSelect($groupId, $chatId);
            }

            if (str_starts_with($data, 'phone_select_')) {
                $phoneId = str_replace('phone_select_', '', $data);

                $phone = UserPhone::find($phoneId);

                if (!$phone) {
                    $this->tg(fn() =>
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
                $this->tg(fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon tanlandi. Iltimos, yuboriladigan xabar matnini kiriting:",
                    'reply_markup' => $this->cancelInlineKeyboard()
                ]));
                return 'ok';
            }



            if ($data === 'cancel_catalog') {
                $user->state = null;
                $user->value = null;
                $user->save();
                $activePhone = $user->phones()->where('is_active', true)->exists();
                $this->tg(fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Catalog tanlash bekor qilindi.',
                    'reply_markup' => $this->mainMenuWithHistoryKeyboard($activePhone)
                ]));
                return 'ok';
            }
            if ($data === 'skip_password' && $user) {

                $phone = $user->phones()
                    ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
                    ->latest()
                    ->first();


                if (!$phone) {
                    $this->tg(fn() =>
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
                    'reply_markup' => $this->cancelInlineKeyboard()

                ]);
                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button('Telefonlarim'),
                    ]);
                sleep(3);
                $this->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Tasdiqlash jarayoni boshlandi 🎉",
                    'reply_markup' => $this->mainMenuWithHistoryKeyboard(true)


                ]));
            }
            if ($data === 'cancel_auth' && $user) {
                return $this->cancelAuth($user, $chatId, $callback->getId());
            }


            return 'ok';
        } elseif ($update->getMessage()) {
            $message = $update->getMessage();


            $from = $message->get('from');
            $chat = $message->get('chat');
            $text = trim($message->getText() ?? '');


            $chatId = $chat['id'] ?? null;
            $firstName = $from['first_name'] ?? null;
            $telegramUserId = $from['id'] ?? null;
        }



        $user = User::where('telegram_id', "$telegramUserId")->first();
        $userState = $user?->state ?? null;

        if (!$user && $text !== '/start') {
            $this->tg(
                fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Iltimos, boshlash uchun /start ni bosing ✅',
                ])
            );
            return 'ok';
        }



        $contact = $message->getContact();
        if (!$message) {
            return 'ok';
        }
        if ($userState === 'creating_catalog' && $text) {
            $catalog = \App\Models\Catalog::create([
                'user_id' => $user->id,
                'title' => $text,
                'peers' => json_encode([]),
            ]);

            $user->state = 'adding_peers_to_catalog';
            $user->value = $catalog->id;
            $user->save();
            $keyboard = (new Keyboard)->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text' => 'To‘xtatish',
                        'callback_data' => 'cancel_catalog', // callback ishlaydi
                    ])
                ]);

            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Catalog yaratildi! Endi peerlarni alohida qo‘shing.  Masalan: \n @grouplink yoki -100123456789 (group/channel ID). Yakunlash uchun /done yozing.",
                'reply_markup' => $keyboard,
            ]));
            return "ok";
        }
        if ($userState === 'adding_peers_to_catalog' && $text) {

            if ($text === '/done') {
                $user->state = null;
                $user->value = null;
                $user->save();

                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button('Cataloglar'),
                    ]);

                $this->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "✅ Catalog yaratish yakunlandi!",
                        'reply_markup' => $keyboard
                    ])
                );
            } else {

                $catalog = \App\Models\Catalog::find($user->value);

                $peers = json_decode($catalog->peers ?? '[]', true);

                // yangi peer qo‘shamiz
                $peers[] = trim($text);

                $catalog->peers = json_encode($peers);
                $catalog->save();

                // umumiy ro‘yxatni chiroyli qilib chiqaramiz
                $listText = "📌 *Joriy peerlar ro‘yxati:*\n\n";
                foreach ($peers as $index => $peer) {
                    $num = $index + 1;
                    $listText .= "{$num}. `{$peer}`\n";
                }

                $listText .= "\n➕ Keyingi peer yuboring yoki /done bilan yakunlang.";

                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);

                $this->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $listText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => $cancelKeyboard
                    ])
                );
            }
        }

        if (($text === '❌ Bekor qilish' && $user) || ($text === 'Menyu' && $user)) {
            return $this->cancelAuth($user, $chatId);
        }

        if ($text === '/start') {
            Log::info('work');

            if (!$user) {
                $user = User::firstOrCreate(
                    ['telegram_id' => $telegramUserId],
                    [
                        'name' => $firstName,
                        'state' => null,
                    ]
                );
            }

            // eski jarayonlarni tozalash
            $user->state = null;
            $user->save();

            $user->phones()
                ->whereIn('state', [
                    'waiting_code',
                    'waiting_password',
                    'waiting_code2'
                ])
                ->update([
                    'state' => 'cancelled',
                    'code' => null
                ]);

            $hasActivePhone = $user->phones()
                ->where('is_active', true)
                ->exists();
            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Salom, $firstName!",
                'reply_markup' => $this->mainMenuWithHistoryKeyboard($hasActivePhone)
            ]));

            return;
        }

        if (($text === 'Cataloglar' && $user) || ($text === '/catalogs' && $user)) {
            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Iltimos, catalog tanlang:',
                'reply_markup' => $this->buildCatalogKeyboard($user->id, 1)
            ]));

            return 'ok';
        }
        if (($text === 'Habar yuborish' && $user) || ($text === '/send' && $user)) {
            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Iltimos, xabar yuboriladigan catalogni tanlang:',
                'reply_markup' => $this->buildCatalogKeyboard($user->id, 1)
            ]));

            return 'ok';
        }





        if ($contact || ($user->state === 'waiting_phone' && $text)) {
            if (!$user->oferta_read) {
                $keyboard->row([
                    Keyboard::button([
                        'text' => 'Oferta bilan tanishib chiqdim',
                    ])
                ]);
                $user->state = null;

                $user->save();
                $this->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Avval Ofertani o‘qib chiqing',
                        'reply_markup' => $keyboard,
                    ])
                );
                return 'ok';
            }

            if ($contact) {
                $phoneNumber = $contact->getPhoneNumber();
            } else {
                $phoneNumber = $text;
            }

            $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+' . $phoneNumber;
            }

            if (!$contact) {
                if (!preg_match('/^\+[1-9]\d{6,14}$/', $phoneNumber)) {
                    $this->tg(
                        fn() =>
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "Telefon raqami noto‘g‘ri formatda ❌\n Masalan: +998901234567",
                            'reply_markup' => $this->cancelInlineKeyboard()

                        ])
                    );
                    return 'ok';
                }
            }
            $lockKey = "telegram_verify_lock_{$phoneNumber}_{$user->id}";

            if (Cache::has($lockKey)) {
                return 'ok';
            }

            Cache::put($lockKey, true, now()->addMinutes(10));

            TelegramAuthJob::dispatch($phoneNumber, $user->id)
                ->onQueue('telegram');

            UserPhone::updateOrCreate(
                ['user_id' => $user->id, 'phone' => $phoneNumber],
                [
                    'state' => 'waiting_code'
                ]
            );
            $user->state = 'waiting_code';
            $user->save();

            $cancelKeyboard = (new Keyboard)->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text' => "❌ Bekor qilish",
                        'callback_data' => 'cancel_auth'
                    ]),
                ]);
            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Rahmat, $firstName! Iltimos, sizga kelgan code-ni ikkiga bo‘lib ketma-ket kiriting.\n\n" .
                    "Masalan, code 12345 bo‘lsa, birinchi 123 kiriting, keyin ikkinchi qismini: 45.",
                'reply_markup' => $cancelKeyboard

            ]));
            return 'ok';
        }
        if ($user->state === 'waiting_code' && $text) {
            $phone = $user->phones()->where('state', 'waiting_code')->latest()->first();
            if (!$phone) {
                $user->state = null;
                $user->save();
                $this->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Hatolik",
                    'reply_markup' => $this->mainMenuWithHistoryKeyboard(true)
                ]));
            }

            if (strlen($text) >= 5) {

                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);

                $this->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Xatolik: Iltimos, code-ni ikki qismga bo‘lib ketma-ket kiriting! \n Bu code boshqa ishlamaydi. Jarayonni boshidan boshlang.",
                    'reply_markup' => $cancelKeyboard
                ]));
                return 'error';
            }

            $phone->code = $text;
            $phone->state = 'waiting_code2';
            $phone->save();
            $user->state = 'waiting_code2';
            $user->save();

            $cancelKeyboard = (new Keyboard)->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text' => "❌ Bekor qilish",
                        'callback_data' => 'cancel_auth'
                    ]),
                ]);

            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅Yaxshi! Endi code-ning ikkinchi qismini kiriting:",
                'reply_markup' => $cancelKeyboard
            ]));
            return 'ok';
        }
        if ($user->state === 'waiting_code2' && $text) {
            $phone = $user->phones()->where('state', 'waiting_code2')->latest()->first();
            if (!$phone) {
                $user->state = null;
                $user->save();
                $this->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Hatolik",
                    'reply_markup' => $this->mainMenuWithHistoryKeyboard(true)
                ]));
            }
            $phone->code = $phone->code . $text;

            if (strlen($phone->code) < 5 || strlen($phone->code) > 5) {
                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);

                $this->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Xatolik: Code umumiy 5 ta raqamdan iborat bo‘lishi kerak. Iltimos, jarayonni boshidan boshlang.",
                    'reply_markup' => $cancelKeyboard
                ]));
                return 'error';
            }


            TelegramVerifyJob::dispatch($phone->phone, $user->id, $phone->code, null)
                ->onQueue('telegram');

            $phone->code = null;
            $phone->state = 'loggin_process';
            $phone->save();

            $user->state = null;
            $user->save();

            $keyboard = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button('Telefonlarim'),
                ]);
            $this->tg(
                fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon raqamingiz qoshildi",
                    'reply_markup' => $keyboard
                ])
            );
        }
        if ($user->state === 'waiting_password') {

            if ($text) {
                $phone = $user->phones()->where('state', 'waiting_password')->latest()->first();

                if ($phone) {

                    TelegramVerifyJob::dispatch($phone->phone, $user->id, $phone->code, null)
                        ->onQueue('telegram');
                    $phone->code = null;
                    $phone->state = 'loggin_process';
                    $phone->save();
                    $reply_markup = Keyboard::make()
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->row([
                            Keyboard::button('Telefonlarim'),
                        ]);
                    $this->tg(fn() =>

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Tasdiqlash jarayonini boshlandi🎉",
                        'reply_markup' => $reply_markup
                    ]));
                    return 'ok';
                }
            }
            return 'ok';
        }

        if ($text === 'Telefonlarim' || $text === '/phones') {

            $userPhones = $user->phones()->get();

            if ($userPhones->isEmpty()) {
                $this->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Sizda telefon raqamlar yo‘q.\nIltimos, yangi raqam yuboring 👇",
                        'reply_markup' => Keyboard::make()
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->row([
                                Keyboard::button([
                                    'text' => '📱 Telefon Raqam Qoshish',
                                ]),
                                Keyboard::button([
                                    'text' => '❌ Bekor qilish',
                                ])
                            ])
                    ])
                );
                return 'ok';
            }

            $this->tg(
                fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "📱 Telefon raqamini tanlang:",
                    'reply_markup' => $this->buildPhoneSelectKeyboard($userPhones),
                ])
            );

            return 'ok';
        }



        if ($userState === 'phone_selected' && $text) {
            $phoneData = json_decode($user->value, true);
            $phoneId = $phoneData['phone_id'] ?? null;
            $phone = UserPhone::find($phoneId);
            if (!$phone) {
                $this->tg(fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon topilmadi. Iltimos, qaytadan tanlang."
                ]));
                return 'ok';
            }

            $phoneData['message_text'] = $text;
            $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
            $user->state = 'message_configured';
            $user->save();
            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Xabar matni saqlandi! Endi necha marta yuborilishini kiriting:",
                'reply_markup' => $this->cancelInlineKeyboard()
            ]));
        }
        if ($userState === 'message_configured' && is_numeric($text)) {

            $loopCount = (int) $text;
            $phoneData = json_decode($user->value, true);
            $phoneData['loop_count'] = $loopCount;

            $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);

            if ($loopCount > 1) {

                $user->state = 'loop_count_configured';
                $user->save();

                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->row(['🕐 1 soat', '🕑 2 soat'])
                    ->row(['🕒 3 soat', '🕓 4 soat'])
                    ->row(['🕕 6 soat', '❌ Bekor qilish']);
                $this->tg(
                    fn() =>

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Intervalni tanlang yoki daqiqada kiriting (kamida 60):",
                        'reply_markup' => $keyboard
                    ])
                );

                return 'ok';
            }

            // loopCount = 1 bo‘lsa
            $phoneData['interval'] = 0;
            $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
            $user->state = 'ready_to_create';
            $user->save();

            return $this->createMessageGroup($user, $chatId);
        }

        $intervalMap = [
            '🕐 1 soat' => 60,
            '🕑 2 soat' => 120,
            '🕒 3 soat' => 180,
            '🕓 4 soat' => 240,
            '🕕 6 soat' => 360,
        ];

        if ($userState === 'loop_count_configured') {

            // 🔹 Button orqali
            if (isset($intervalMap[$text])) {

                $interval = $intervalMap[$text];

                // 🔹 Qo‘lda yozilgan raqam
            } elseif (is_numeric($text) && (int)$text >= 60) {

                $interval = (int) $text;
            } else {
                $this->tg(
                    fn() =>

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Iltimos, intervalni to‘g‘ri tanlang (kamida 60 daqiqa).'
                    ])
                );
                return 'ok';
            }

            $phoneData = json_decode($user->value, true);
            $phoneData['interval'] = $interval;

            $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
            $user->state = 'ready_to_create';
            $user->save();

            return $this->createMessageGroup($user, $chatId);
        }

        if ($text === 'Yuborilgan xabarlar natijasi' || $text === '/history') {
            $this->tg(
                fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => '📨 Xabarlar:',
                    'reply_markup' => $this->buildGroupKeyboard($user, 1)
                ])
            );

            return 'ok';
        }
        if ($text == "/help") {
            if ($user) {
                $user->state = null;
                $user->save();
                $user->phones()
                    ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
                    ->update([
                        'state' => 'cancelled',
                        'code' => null
                    ]);
            }
            $this->tg(
                fn() =>

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' =>
                    "📌 Buyruqlar ro‘yxati:\n\n" .
                        "/start — Botni qayta ishga tushirish\n" .
                        "/history —  Yuborilgan habarlarni korish\n" .
                        "/phones — Telefonlarim\n" .
                        "/catalogs — Cataloglar ro‘yxati\n" .
                        "/help — Yordam olish\n",
                    'reply_markup' => $this->mainMenuWithHistoryKeyboard(true)

                ])
            );
        }

        if (preg_match('/^❌ To‘xtatish (\d+)$/', $text, $matches)) {
            $groupId = (int) $matches[1];
            CleanupScheduledJob::dispatch($groupId)->onQueue('telegram');
            // sleep(2);
            Log::info('work');
            $this->handleGroupSelect($groupId, $chatId);
        }

        if (preg_match('/^🔄 Malumotlarni yangilash (\d+)$/', $text, $matches)) {
            $groupId = (int) $matches[1];
            $this->handleGroupSelect($groupId, $chatId);
        }

        if ($text === 'Qollanma') {
    $manualText = <<<EOT
📘 *Qollanma*

Botni ishlatish bo‘yicha bosqichma-bosqich ko‘rsatmalar:

1️⃣ *Asosiy menyu*  
Start tugmasini bosganingizdan so‘ng quyidagi tugmalar mavjud:  
- 📱 Telefon Raqam Qoshish  
- 📞 Mening Telefon Raqamlarim  
- 🗂 Cataloglar  
- 📊 Yuborilgan Xabarlar Natijasi  
- 📖 Qollanma  
- 📄 Offerta  

2️⃣ *Telefon Raqam Qoshish*  
- Tugmani bosganda telefon raqamingizni yozing: contact uslubida yoki +998901234567 formatida.  
- Telefon raqam yuborilgandan so‘ng, bot sizga *2FA code* yuboradi. Telegram xavfsizligi tufayli kod ikkiga bo‘linadi:  
  - Masalan, code: `12345`  
  - Avval `12` ni yuboring, keyin `345` qismini yuboring.  
- *Muhim:* Telegram akkauntingizda 2FA (ikki faktorli autentifikatsiya) **o‘chirilgan bo‘lishi kerak**. Agar 2FA yoqilgan bo‘lsa, kod ishlamaydi.
- Telefon raqam qabul qilinadi va xabar keladi.  
- Tasdiqlashga bir oz vaqt ketishi mumkin. Agar raqam Telefonlar ro‘yxatida faol ko‘rsatilmasa, qayta urinib ko‘ring.  
- Telefon raqamni o‘chirish faqat shu tizimdan amalga oshiriladi; boshqa qurilmalarda raqam faoliyatini yo‘qotmaydi.  

3️⃣ *Mening Telefon Raqamlarim*  
- Tugmani bosganda foydalanuvchining barcha telefon raqamlari ro‘yxati ko‘rsatiladi.  
- Raqamni tanlab, kerak bo‘lsa uni o‘chirish mumkin.  

4️⃣ *Cataloglar*  
- Tugmani bosganda yangi Catalog (papka) yaratishingiz mumkin va unga nom beriladi.  
- Keyin guruhlar (peerlar) qo‘shiladi. Peerlar – bu Telegram username yoki group ID.  
- Peerlarni qo‘shish jarayonida har bir qo‘shilgan element ostida `P.S @username` ko‘rsatiladi.  
- Oxirida `/done` tugmasi bilan katalog yaratiladi va barcha peerlar tasdiqlanadi.  

5️⃣ *Yuborilgan Xabarlar Natijasi*  
- Oxirgi 10 ta yuborilgan xabar ro‘yxati ko‘rsatiladi.  
- Jarayonda bo‘lgan xabarlar ham ko‘rinadi, ularning pastida “To‘xtatish” tugmasi mavjud.  
- Har bir xabarda necha marta yuborilgani va qaysi papkaga yuborilgani ko‘rsatiladi.  
- Agar telefon raqamlar ko‘p bo‘lsa, “Xabar Yuborish” tugmasi chiqadi.  
  - Foydalanuvchi telefon raqamni tanlaydi, xabar papkasini tanlaydi, interval va necha marta yuborishni belgilaydi.  
  - Keyin xabar yuboriladi. Natijalarni shu bo‘limda kuzatish mumkin.  

6️⃣ *Qollanma*  
- Bu bo‘lim botni ishlatish bo‘yicha batafsil tushuntirishlarni o‘z ichiga oladi (hozirgi matn).  



EOT;

    $this->tg(
        fn() =>
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $manualText,
            'parse_mode' => 'Markdown',
            'reply_markup' => $this->cancelInlineKeyboard()
            
        ])
    );
}


        if ($text === 'Oferta bilan tanishib chiqdim') {
            $user->oferta_read = true;
            $user->save();
            $keyboard = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true);
            $keyboard->row([
                Keyboard::button([
                    'text' => '📱 Telefon raqamini yuborish',
                    'request_contact' => true,
                ])
            ]);
            $hasActivePhone = $user->phones()->where('is_active', true)->exists();
            $this->tg(
                fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Iltimos, telefon raqamingizni Contact tugmasi yordamida yoki +998991234567 formatida kiriting.',
                    'reply_markup' => $this->cancelInlineKeyboard()
                ])
            );
        }
        if ($text === '📱 Telefon Raqam Qoshish') {
            $keyboard = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true);
            $keyboard->row([
                Keyboard::button([
                    'text' => 'Oferta bilan tanishib chiqdim',
                ])
            ]);

            if (!$user->oferta_read) {
                $this->tg(
                    fn() =>
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Avval Ofertani o‘qib chiqing',
                        'reply_markup' => $keyboard,
                    ])
                );

                return "ok";
            }
            $user->state = 'waiting_phone';
            $user->save();
            $keyboard->row([
                Keyboard::button([
                    'text' => '📱 Telefon raqamini yuborish',
                    'request_contact' => true,
                ])
            ]);
            $this->tg(
                fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Iltimos Telofon raqamini Conntact uslida yoki +998991234567 shu formatda jonating',
                    'reply_markup' => $this->cancelInlineKeyboard()

                ])
            );
        }















        return 'ok';
    }

    private function mainMenuWithHistoryKeyboard(bool $hasActivePhone = true)
    {
        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true);

        $keyboard
            ->row([
                Keyboard::button([
                    'text' => '📱 Telefon Raqam Qoshish',
                ]),
                Keyboard::button('Telefonlarim'),
            ])
            ->row([
                Keyboard::button('Cataloglar'),
                Keyboard::button('Yuborilgan xabarlar natijasi'),
            ])
            ->row([
                Keyboard::button('Qollanma'),
                Keyboard::button('Offerta'),
            ]);
        if ($hasActivePhone) {
            $keyboard->row([
                Keyboard::button('Habar yuborish'),
            ]);
        }

        return $keyboard;
    }


    private function buildCatalogKeyboard(int $userId, int $page = 1)
    {
        // Faqat user_id bo'yicha filtr
        $catalogs = Catalog::where('user_id', $userId)
            ->orderBy('id')
            ->get()
            ->toArray();

        $perPage = 4;
        $chunks = array_chunk($catalogs, $perPage);
        $pageCatalogs = $chunks[$page - 1] ?? [];

        $keyboard = (new Keyboard)->inline();

        $keyboard->row([
            Keyboard::inlineButton([
                'text' => '➕ Yangi Catalog yaratish',
                'callback_data' => 'catalog_create'
            ])
        ]);

        $catalogButtons = [];
        foreach ($pageCatalogs as $catalog) {
            $catalogButtons[] = Keyboard::inlineButton([
                'text' => $catalog['title'],
                'callback_data' => 'catalog_select_' . $catalog['id']
            ]);
        }

        foreach (array_chunk($catalogButtons, 2) as $chunk) {
            $keyboard->row($chunk);
        }

        $navButtons = [];

        if ($page > 1) {
            $navButtons[] = Keyboard::inlineButton([
                'text' => '⬅ Previous',
                'callback_data' => 'catalog_page_' . ($page - 1)
            ]);
        }

        if ($page < count($chunks)) {
            $navButtons[] = Keyboard::inlineButton([
                'text' => 'Next ➡',
                'callback_data' => 'catalog_page_' . ($page + 1)
            ]);
        }

        if ($navButtons) {
            $keyboard->row($navButtons);
        }

        $keyboard->row([
            Keyboard::inlineButton([
                'text' => '❌ Catalog tanlashni bekor qilish',
                'callback_data' => 'cancel_catalog'
            ])
        ]);

        return $keyboard;
    }

    private function buildPhoneKeyboard(array $phones)
    {
        $keyboard = (new Keyboard)->inline();

        if (empty($phones)) {
            // Telefonlar yo'q bo'lsa, shunchaki xabar uchun tugma
            $keyboard = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button([
                        'text' => '📱 Telefon raqamini yuborish',
                        'request_contact' => true,
                    ])
                ]);
        } else {
            // Telefonlar mavjud bo'lsa, har biri alohida qatorga
            foreach ($phones as $phone) {
                $keyboard->row([
                    Keyboard::inlineButton([
                        'text' => $phone['phone'],
                        'callback_data' => 'phone_select_' . $phone['id']
                    ])
                ]);
            }

            // Bekor qilish tugmasi
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => '❌ Tanlashni bekor qilish',
                    'callback_data' => 'cancel_auth'
                ])
            ]);
        }

        return $keyboard;
    }
    private function buildPhoneSelectKeyboard($phones, int $page = 1)
    {
        $perPage = 4;

        // collection → array
        $phonesArray = $phones instanceof \Illuminate\Support\Collection
            ? $phones->values()->toArray()
            : $phones;

        $chunks = array_chunk($phonesArray, $perPage);
        $pagePhones = $chunks[$page - 1] ?? [];

        $keyboard = (new Keyboard)->inline();

        // 📞 Phone buttons
        foreach ($pagePhones as $index => $phone) {

            $status = $phone['is_active'] ? '✅ Faol' : '⚪️ No faol';

            $text = (($page - 1) * $perPage + $index + 1)
                . '. ' . $phone['phone'] . ' ' . $status;

            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $text,
                    'callback_data' => 'phone_choose_' . $phone['id'],
                ])
            ]);
        }

        // ⬅ ➡ Navigation
        $navButtons = [];

        if ($page > 1) {
            $navButtons[] = Keyboard::inlineButton([
                'text' => '⬅ Previous',
                'callback_data' => 'phone_page_' . ($page - 1),
            ]);
        }

        if ($page < count($chunks)) {
            $navButtons[] = Keyboard::inlineButton([
                'text' => 'Next ➡',
                'callback_data' => 'phone_page_' . ($page + 1),
            ]);
        }

        if ($navButtons) {
            $keyboard->row($navButtons);
        }

        // ❌ Cancel
        $keyboard->row([
            Keyboard::inlineButton([
                'text' => '❌ Tanlashni bekor qilish',
                'callback_data' => 'cancel_auth',
            ])
        ]);

        return $keyboard;
    }

    private function buildGroupKeyboard(User $user)
    {
        // Foydalanuvchining telefonlari
        $phoneIds = $user->phones()->pluck('id')->toArray();

        // Guruhlarni olish, eng yangi oxirgisini olish uchun latest va take
        $groups = MessageGroup::withCount('messages')
            ->with(['messages' => function ($q) {
                $q->latest();
            }])
            ->whereIn('user_phone_id', $phoneIds)
            ->latest() // eng yangi birinchi
            ->take(10) // oxirgi 10 ta
            ->get();

        $keyboard = (new Keyboard)->inline();

        foreach ($groups as $group) {
            $text = optional($group->messages->first())->message_text ?? 'Xabar yo‘q';

            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => mb_strimwidth($text, 0, 30, '...') . ' — ' . $group->messages_count,
                    'callback_data' => 'group_select_' . $group->id
                ])
            ]);
        }

        // Pagination olib tashlandi
        // $navButtons = [];

        // Yopish tugmasi
        $keyboard->row([
            Keyboard::inlineButton([
                'text' => '❌ Yopish',
                'callback_data' => 'cancel_auth'
            ])
        ]);

        return $keyboard;
    }

    protected function handleGroupSelect(string $groupId, int $chatId)
    {
        RefreshGroupStatusJob::dispatch($groupId)->onQueue('telegram');

        $group = MessageGroup::with('messages')->find($groupId);

        if (!$group || $group->messages->isEmpty()) {
            $this->tg(
                fn() =>
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Guruh yoki xabarlar topilmadi."
                ])
            );
            return;
        }

        $messages = $group->messages;

        $text  = "📊 Guruh ma'lumotlari\n\n";
        $text .= "📌 Guruh ID: {$group->id}\n";
        $text .= "🕒 Boshlangan: " . optional($messages->min('send_at'))->format('Y-m-d H:i') . "\n";
        $text .= "⏰ Tugashi: " . optional($messages->max('send_at'))->format('Y-m-d H:i') . "\n\n";

        $text .= "📝 Message:\n";
        $text .= $messages->first()->message_text . "\n\n";

        $text .= "👥 Peerlar bo‘yicha holat:\n";
        $messages->groupBy('peer')->each(function ($peerMessages, $peer) use (&$text) {
            $counts = $peerMessages->groupBy('status')->map->count();

            $statusText = collect([
                'pending'   => '🕓',
                'scheduled' => '📅',
                'sent'      => '✅',
                'failed'    => '❌',
                'canceled'  => '🚫',
            ])
                ->filter(fn($icon, $status) => ($counts[$status] ?? 0) > 0)
                ->map(fn($icon, $status) => "{$icon} {$status}: {$counts[$status]}")
                ->implode(' | ');

            $text .= "• {$peer} — {$statusText}\n";
        });

        // Keyboard yaratish
        $replyKeyboard = Keyboard::make()->setResizeKeyboard(true);

        // Agar hammasi 'sent' bo‘lmasa
        $hasPendingOrScheduled = $messages->contains(fn($msg) => in_array($msg->status, ['scheduled', 'pending']));

        if ($hasPendingOrScheduled) {
            $replyKeyboard->row([
                Keyboard::button("❌ To‘xtatish {$group->id}"),
                Keyboard::button("🔄 Malumotlarni yangilash {$group->id}")
            ]);
        }


        // Doimiy menu tugmalari
        $replyKeyboard->row([
            Keyboard::button("Yuborilgan xabarlar natijasi"),
            Keyboard::button("Cataloglar")
        ])->row([
            Keyboard::button("Menyu")
        ]);

        $this->tg(
            fn() =>
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => $replyKeyboard
            ])
        );
    }

    protected function createMessageGroup($user, $chatId)
    {
        $data = json_decode($user->value, true);

        $phone = UserPhone::find($data['phone_id']);
        if (!$phone) {
            $this->tg(fn() =>

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Telefon topilmadi."
            ]));
            return 'ok';
        }

        $group = MessageGroup::create([
            'user_phone_id' => $phone->id,
            'status' => 'pending'
        ]);

        $catalog = Catalog::find($data['catalog_id']);

        $peers = json_decode($catalog->peers, true);

        $loopCount = $data['loop_count'];
        $interval  = $data['interval']; // 0 bo‘lishi mumkin
        $message   = $data['message_text'];

        foreach ($peers as $peer) {
            for ($i = 0; $i < $loopCount; $i++) {
                TelegramMessage::create([
                    'message_group_id' => $group->id,
                    'peer' => $peer,
                    'message_text' => $message,
                    'send_at' => $interval > 0
                        // ? now()->addSeconds($i * $interval)
                        ? now()->addMinutes($i * $interval)
                        : now(),
                    'status' => 'pending',
                ]);
            }
        }

        SendTelegramMessages::dispatch($group->id)->onQueue('telegram');
        $this->tg(fn() =>

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ Xabarlar jadvali yaratildi va navbatga qo‘yildi. \n/history - orqali ularni korishingiz mumkin ",
            'reply_markup' => $this->mainMenuWithHistoryKeyboard()
        ]));

        $user->state = null;
        $user->value = null;
        $user->save();

        return 'ok';
    }
    protected function cancelInlineKeyboard()
    {
        return (new Keyboard)->inline()
            ->row([
                Keyboard::inlineButton([
                    'text' => '❌ Tanlashni bekor qilish',
                    'callback_data' => 'cancel_auth'
                ])
            ]);
    }
    private function cancelAuth(User $user, int $chatId, ?string $callbackQueryId = null)
    {
        // 🔹 Telefonlardagi auth jarayonlarini bekor qilish
        $user->phones()
            ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
            ->update([
                'state' => 'cancelled',
                'code' => null
            ]);

        // 🔹 User state tozalash
        $user->state = null;
        $user->save();

        // 🔹 Agar callback bo‘lsa — answerCallbackQuery
        if ($callbackQueryId) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'Bekor qilindi',
                'show_alert' => false,
            ]);
        }

        // 🔹 Asosiy menyu
        $hasActivePhone = $user->phones()->where('is_active', true)->exists();
        $this->tg(fn() =>

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Bekor qilindi.',
            'reply_markup' => $this->mainMenuWithHistoryKeyboard($hasActivePhone)
        ]));

        return 'ok';
    }
    protected function tg(callable $fn)
    {
        try {
            return $fn();
        } catch (TelegramResponseException $e) {

            // 🔕 User botni block qilgan — jim yutamiz
            if (
                str_contains($e->getMessage(), 'bot was blocked by the user') ||
                str_contains($e->getMessage(), 'user is deactivated')
            ) {
                return null;
            }

            // ⚠️ boshqa telegram xatolarni log qilamiz
            Log::warning('Telegram API error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
