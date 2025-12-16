<?php

namespace App\Http\Controllers\Bot;

use App\Models\User;
use Telegram\Bot\Api;
use App\Models\Catalog;
use App\Models\UserPhone;
use App\Models\MessageGroup;
use App\Jobs\TelegramAuthJob;
use App\Jobs\TelegramVerifyJob;
use App\Models\TelegramMessage;
use App\Jobs\SendTelegramMessages;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use App\Http\Controllers\Controller;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_TOKEN'));
    }

    public function webhook()
    {
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
                    'show_alert' => false
                ]);
            } catch (\Telegram\Bot\Exceptions\TelegramOtherException $e) {
                Log::warning('Callback query expired', ['exception' => $e]);
                return 'ok';
            }
            if (!$callback instanceof \Telegram\Bot\Objects\CallbackQuery) {
                Log::error('Callback query not valid', ['callback' => $callback]);
                return 'ok';
            }


            $data = $callback->getData();
            $chatId = $callback->getMessage()->getChat()->getId();
            $telegramUserId = $callback->getFrom()->getId();

            $user = User::where('telegram_id', $telegramUserId)->first();

            if (str_starts_with($data, 'catalog_page_')) {

                $page = (int) str_replace('catalog_page_', '', $data);

                $this->telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $callback->getMessage()->getMessageId(),
                    'text' => 'Iltimos, catalog tanlang:',
                    'reply_markup' => $this->buildCatalogKeyboard($page)
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

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Iltimos, yangi catalog nomini kiriting:",
                    'reply_markup' => $cancelKeyboard
                ]);
            }
            if (str_starts_with($data, 'catalog_select_')) {
                $phones = $user->phones()->where('is_active', true)->get()->toArray();
                $keyboard = $this->buildPhoneKeyboard($phones);
                $user->state = 'phone_selected';
                $json = json_encode([

                    'catalog_id' => str_replace('catalog_select_', '', $data),
                    'phone_id' => null,
                    'message_text' => null,
                    'interval' => null,
                    'loop_count' => null
                ], JSON_UNESCAPED_UNICODE);
                $user->value = $json;
                $user->save();
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => empty($phones) ? "Telefonlar mavjud emas." : "Telefonni tanlang:",
                    'reply_markup' => $keyboard
                ]);
            }
            if (str_starts_with($data, 'phone_select_')) {
                $phoneId = str_replace('phone_select_', '', $data);

                $phone = UserPhone::find($phoneId);

                if (!$phone) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Telefon topilmadi."
                    ]);
                    return 'ok';
                }

                $user = $phone->user;

                $user->state = 'phone_selected';
                $phoneData = json_decode($user->value, true);
                $phoneData['phone_id'] = $phone->id;
                $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
                $user->save();

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon tanlandi. Iltimos, yuboriladigan xabar matnini kiriting:"
                ]);
                return 'ok';
            }
            if ($data === 'cancel_catalog') {

                $activePhone = $user->phones()->where('is_active', true)->exists();
                if ($activePhone) {
                    $reply_markup = Keyboard::make()
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->row([
                            Keyboard::button('Cataloglar'),
                        ])
                        ->row([
                            Keyboard::button('Telefonlarim'),
                        ])
                        ->row([
                            Keyboard::button([
                                'text' => '📱 Telefon raqamini yuborish',
                                'request_contact' => true,
                            ])

                        ]);
                } else {
                    $reply_markup = Keyboard::make()
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->row([
                            Keyboard::button([
                                'text' => '📱 Telefon raqamini yuboring',
                                'request_contact' => true,
                            ])
                        ])
                        ->row([
                            Keyboard::button('Telefonlarim'),
                        ]);
                }

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Catalog tanlash bekor qilindi.',
                    'reply_markup' => $reply_markup
                ]);
                return 'ok';
            }
            if ($data === 'skip_password' && $user) {

                $phone = $user->phones()
                    ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
                    ->latest()
                    ->first();
                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);

                if (!$phone) {

                    $this->telegram->answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => "Holat topilmadi",
                        'show_alert' => true,
                        'reply_markup' => $cancelKeyboard
                    ]);
                    return 'ok';
                }

                TelegramVerifyJob::dispatch(
                    $phone->phone,
                    $user->id,
                    $phone->code,
                    null
                )->onQueue('telegram');

                $phone->update([
                    'code' => null,
                    'state' => 'loggin_process'
                ]);

                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callback->getId(),
                    'text' => "Password o'tkazib yuborildi",
                    'show_alert' => false,
                    'reply_markup' => $cancelKeyboard
                ]);
                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button('Telefonlarim'),
                    ]);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Tasdiqlash jarayoni boshlandi 🎉",
                    'reply_markup' => $keyboard

                ]);
            }
            if ($data === 'cancel_auth' && $user) {
                $user->phones()
                    ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
                    ->update([
                        'state' => 'cancelled',
                        'code' => null
                    ]);
                $user->state = null;
                $user->save();
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callback->getId(),
                    'text' => "Bekor qilindi",
                    'show_alert' => false,
                ]);

                $activePhone = $user->phones()->where('is_active', true)->exists();
                if ($activePhone) {
                    $reply_markup = Keyboard::make()
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->row([
                            Keyboard::button('Cataloglar'),
                        ])
                        ->row([
                            Keyboard::button([
                                'text' => '📱 Telefon raqamini yuborish',
                                'request_contact' => true,
                            ])
                        ])
                        ->row([
                            Keyboard::button('Telefonlarim'),
                        ]);
                } else {
                    $reply_markup = Keyboard::make()
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->row([
                            Keyboard::button([
                                'text' => '📱 Telefon raqamini yuboring',
                                'request_contact' => true,
                            ]),
                        ])
                        ->row([
                            Keyboard::button('Telefonlarim'),
                        ]);
                }

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Bekor qilindi.',
                    'reply_markup' => $reply_markup
                ]);
            }
            return 'ok';
        }
        $message = $update->getMessage();
        if ($message) {
            $from = $message->getFrom();
            $telegramUserId = $from->getId();
            $firstName = $from->getFirstName();
            $chatId = $message->getChat()->getId();
        } else {
            $from = null;
            $telegramUserId = null;
            $firstName = null;
            $chatId = null;
        }
            $text = trim($message->getText() ?? '');
            $user = User::where('telegram_id', "$telegramUserId")->first();
            $state = null;
            $userState = $user?->state ?? null;
        if ($user) {
            $state = $user->phones()
                ->whereNotNull('state')
                ->latest()
                ->value('state');
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

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Catalog yaratildi! Endi peerlarni alohida qo‘shing. Yakunlash uchun /done yozing."
            ]);
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
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Catalog yaratish yakunlandi!",
                    'reply_markup' => $keyboard
                ]);
            } else {
                $catalog = \App\Models\Catalog::find($user->value);
                $peers = json_decode($catalog->peers ?? '[]', true);
                $peers[] = $text;
                $catalog->peers = json_encode($peers);
                $catalog->save();

                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Peer qo‘shildi! Keyingi peer yoki /done bilan yakunlang.",
                    'reply_markup' => $cancelKeyboard
                ]);
            }
        }
        if ($text === '❌ Bekor qilish') {

            $user?->phones()
                ->whereIn('state', ['waiting_code', 'waiting_password', 'waiting_code2'])
                ->update([
                    'state' => 'cancelled',
                    'code' => null
                ]);
            $user->state = null;
            $user->save();
            $activePhone = $user?->phones()->where('is_active', true)->exists();
            if ($activePhone) {
                $reply_markup = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button('Cataloglar'),
                    ])
                    ->row([
                        Keyboard::button([
                            'text' => '📱 Telefon raqamini yuborish',
                            'request_contact' => true,
                        ])
                    ]);
            } else {
                $reply_markup = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button([
                            'text' => '📱 Telefon raqamini yuborish',
                            'request_contact' => true,
                        ])
                    ]);
                $response = $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Salom! Iltimos, telefon raqamingizni yuboring:',
                    'reply_markup' => $reply_markup
                ]);
            }
        }
        if ($text === '/start') {
            $activePhone = $user?->phones()->where('is_active', true)->exists();
            if ($activePhone) {
                $reply_markup = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button('Cataloglar'),
                    ])
                    ->row([
                        Keyboard::button([
                            'text' => '📱 Telefon raqamini yuborish',
                            'request_contact' => true,
                        ])
                    ]);
            } else {
                $reply_markup = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button([
                            'text' => '📱 Telefon raqamini yuborish',
                            'request_contact' => true,
                        ])
                    ]);
                $response = $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Salom! Iltimos, telefon raqamingizni yuboring:',
                    'reply_markup' => $reply_markup
                ]);
            }
        }
        if ($text === 'Cataloglar') {

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Iltimos, catalog tanlang:',
                'reply_markup' => $this->buildCatalogKeyboard(1)
            ]);

            return 'ok';
        }
        if ($contact) {

            $phoneNumber = $contact->getPhoneNumber();
            $user = User::firstOrCreate(
                ['telegram_id' => $telegramUserId],
                [
                    'name' => $firstName,
                    'phone' => $phoneNumber,
                    // 'state' => 'waiting_code'
                ]
            );


            TelegramAuthJob::dispatch($phoneNumber, $user->id)
                ->onQueue('telegram');
            UserPhone::updateOrCreate(
                ['user_id' => $user->id, 'phone' => $phoneNumber],
                [
                    'state' => 'waiting_code'
                ]
            );
            $user->state = 'waiting_code';

            $cancelKeyboard = (new Keyboard)->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text' => "❌ Bekor qilish",
                        'callback_data' => 'cancel_auth'
                    ]),
                ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Rahmat, $firstName! Iltimos, sizga kelgan code-ni ikkiga bo‘lib ketma-ket kiriting.\n\n" .
                    "Masalan, code 12345 bo‘lsa, birinchi 123 kiriting, keyin ikkinchi qismini: 45.",
                'reply_markup' => $cancelKeyboard

            ]);
            return 'ok';
        }
        if ($state === 'waiting_code' && $text) {
            $phone = $user->phones()->where('state', 'waiting_code')->latest()->first();
            if (strlen($text) >= 5) {

                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);


                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Xatolik: Iltimos, code-ni ikki qismga bo‘lib ketma-ket kiriting! \n Bu code boshqa ishlamaydi. Jarayonni boshidan boshlang.",
                    'reply_markup' => $cancelKeyboard
                ]);
                return 'error';
            }
            $phone->code = $text;
            $phone->state = 'waiting_code2';
            $phone->save();

            $cancelKeyboard = (new Keyboard)->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text' => "❌ Bekor qilish",
                        'callback_data' => 'cancel_auth'
                    ]),
                ]);


            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅Yaxshi! Endi code-ning ikkinchi qismini kiriting:",
                'reply_markup' => $cancelKeyboard
            ]);
            return 'ok';
        }
        if ($state === 'waiting_code2' && $text) {
            $phone = $user->phones()->where('state', 'waiting_code2')->latest()->first();

            $phone->code = $phone->code . $text;

            if (strlen($phone->code) < 5 || strlen($phone->code) > 5) {
                $cancelKeyboard = (new Keyboard)->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => "❌ Bekor qilish",
                            'callback_data' => 'cancel_auth'
                        ]),
                    ]);


                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Xatolik: Code umumiy 5 ta raqamdan iborat bo‘lishi kerak. Iltimos, jarayonni boshidan boshlang.",
                    'reply_markup' => $keyboard
                ]);
                return 'error';
            }
            $phone->state = 'waiting_password';
            $phone->save();
            $keyboard = (new Keyboard)->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text' => "Password yo'q (o'tkazib yuborish)",
                        'callback_data' => 'skip_password'
                    ]),
                    Keyboard::inlineButton([
                        'text' => "❌ Bekor qilish",
                        'callback_data' => 'cancel_auth'
                    ]),
                ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "$phone->code code qabul qilindi.\n\nAgar sizning hisobingizda ikkinchi darajali himoya (password) o'rnatilgan bo'lsa, iltimos, password-ni kiriting. Agar yo'q bo'lsa, 'Password yo'q (o'tkazib yuborish)' tugmasini bosing.",
                'reply_markup' => $keyboard
            ]);
        }
        if ($state === 'waiting_password') {

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

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Tasdiqlash jarayonini boshlandi🎉",
                        'reply_markup' => $reply_markup
                    ]);
                    return 'ok';
                }
            }
            return 'ok';
        }
        if ($text === 'Telefonlarim') {
            $userPhones = $user->phones()->get();
            if ($userPhones->isEmpty()) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Sizda hech qanday telefon raqamlar ro'yxati yo'q."
                ]);
                return 'ok';
            }
            $phoneList = "Sizning telefon raqamlari ro'yxatingiz:\n\n";
            foreach ($userPhones as $phone) {
                $status = $phone->is_active ? ' (Faol)' : ' No faol';
                $phoneList .= "- " . $phone->phone . $status . "\n";
            }
            $keyboard = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button('Cataloglar'),
                ])
                ->row([
                    Keyboard::button([
                        'text' => '📱 Telefon raqamini yuborish',
                        'request_contact' => true,
                    ])
                ]);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $phoneList,
                'reply_markup' => $keyboard
            ]);
        }
        if ($userState === 'phone_selected' && $text) {
            $phoneData = json_decode($user->value, true);
            $phoneId = $phoneData['phone_id'] ?? null;
            $phone = UserPhone::find($phoneId);
            if (!$phone) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon topilmadi. Iltimos, qaytadan tanlang."
                ]);
                return 'ok';
            }

            $phoneData['message_text'] = $text;
            $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
            $user->state = 'message_configured';
            $user->save();

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Xabar matni saqlandi! Endi yuborish intervalini soniyada kiriting (minimum: 60 daqiqa):"
            ]);
        }
        if ($userState === 'message_configured' && is_numeric($text)) {

            if ((int)$text < 60) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Iltimos, intervalni kamida 60 soniya qilib kiriting."
                ]);
                return 'ok';
            }
            $phoneData = json_decode($user->value, true);
            $phoneId = $phoneData['phone_id'] ?? null;
            $phone = UserPhone::find($phoneId);
            if (!$phone) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon topilmadi. Iltimos, qaytadan tanlang."
                ]);
                return 'ok';
            }

            $phoneData['interval'] = (int) $text;
            $user->value = json_encode($phoneData, JSON_UNESCAPED_UNICODE);
            $user->state = 'interval_configured';
            $user->save();

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Interval saqlandi! Endi necha marta yuborilishini kiriting:"
            ]);
        }
        if ($userState === 'interval_configured' && is_numeric($text)) {
            $phoneData = json_decode($user->value, true);
            $phoneId = $phoneData['phone_id'] ?? null;
            $phone = UserPhone::find($phoneId);
            if (!$phone) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Telefon topilmadi. Iltimos, qaytadan tanlang."
                ]);
            }
            $group = MessageGroup::create([
                'user_phone_id' => $phone->id,
                'status' => 'pending'
            ]);
            $catalog = Catalog::find($phoneData['catalog_id']);
            $peers = json_decode($catalog->peers, true);
            $messageText = $phoneData['message_text'];
            $loop_count = (int) $text;
            $interval = $phoneData['interval'];

            foreach ($peers as $peer) {


                for ($i = 0; $i < $loop_count; $i++) {
                    TelegramMessage::create([
                        'message_group_id' => $group->id,
                        'message_text' => $messageText,
                        'peer' => $peer,
                        'send_at' => now()->addSeconds($i * $interval),
                        'status' => 'pending',
                    ]);
                }
            }
            SendTelegramMessages::dispatch($group->id)
                ->delay(now()->addSeconds(10))
                ->onQueue('telegram');
            // $encode = json_encode($phoneData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Xabarlar jadvali yaratildi va navbatga qo'yildi! Yuborish jarayoni boshlanadi. Siz boshqa cataloglar bilan davom etishingiz mumkin.",
            ]);
        }




        return 'ok';
    }
    private function buildCatalogKeyboard(int $page = 1)
    {
        $catalogs = Catalog::orderBy('id')->get()->toArray();

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
}
