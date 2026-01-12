<?php

return [
    'admin' => [
        'dashboard' => 'Dashboard',
        'main_dashboard' => 'Asosiy Dashboard',

        'users' => 'Foydalanuvchilar',
        'phones' => 'Telefonlar',
        'operations' => 'Operatsiyalar',
        'messages_count' => 'Yuborilgan xabarlar',

        'last_active_users' => 'Oxirgi faol foydalanuvchilar',
        'no_recent_activity' => "So‘nggi faollik topilmadi",
        'messages_per_day' => "Kunlik yuborilgan xabarlar",
        'users_by_operations' => "Foydalanuvchilar bo'yicha operatsiyalar",
        'all_year' => "Butun yil",
        'all_time' => "Butun vaqt",
        'month' => "Oy",
        'day' => "Kun",
        'grouped_bar' => "Habarlar boylab aktiv telefonlar",
        'active' => "Faol",

        // --- Qo'shilgan yangi kalitlar (Users page va confirm va boshqalar)
        'add_user' => 'Foydalanuvchi qo‘shish',
        'search_users' => 'Foydalanuvchini qidirish...',
        'toggle' => 'Tanlash',
        'showing' => 'Ko‘rsatilmoqda',
        'no_telegram' => 'Telegram yo‘q',
        'no_role' => 'Rol belgilanmagan',

        'add_phone' => 'Telefon qo‘shish',
        'details' => 'Batafsil',

        'ban' => 'Bloklash',
        'unban' => 'Blokdan chiqarish',

        'delete' => 'O‘chirish',
        'delete_user' => 'Foydalanuvchini o‘chirish',

        'confirm' => 'Tasdiqlash',
        'cancel' => 'Bekor qilish',
        'continue' => 'Davom etish',
        'confirm_type_name' => 'Tasdiqlash uchun nomni kiriting',
        'confirm_mismatch' => 'Kiritilgan nom mos kelmadi',
        'back' => 'Ortga',

        'success' => 'Muvaffaqiyatli bajarildi',
        'error' => 'Xatolik',
        'server_error' => 'Server xatosi',

        'phone_activated' => 'Telefon faollashtirildi',
        'error_phone_activate' => 'Telefonni faollashtirib bo‘lmadi',
        'year' => 'Yil',
    ],

    // layout may be present in separate file; include if you want:
    'layout' => [
        'profile' => 'Profil',
        'settings' => 'Sozlamalar',
        'logout' => 'Chiqish',
        'page_title' => 'Admin Panel',

    ],

    'operations' => [
        'title' => 'Operatsiyalar',
        'subtitle' => 'Bo‘lim uchun operatsiyalar: :dept',
        'search_placeholder' => 'Message text bo‘yicha qidirish...',
        'filter_all_status' => 'Barchasi',
        'status_pending' => 'Pending',
        'status_scheduled' => 'Scheduled',
        'status_sent' => 'Sent',
        'status_canceled' => 'Canceled',
        'status_failed' => 'Failed',
        'group' => 'Operatsiya',
        'by_user' => 'Foydalanuvchi',
        'text_label' => 'Matn',
        'peer_total' => 'Jami',
        'total' => 'ALL',
        'total_sent' => 'TOTAL SENT',
        'rate' => 'RATE',
        'btn_refresh' => 'Refresh',
        'btn_cancel' => 'Cancel',
        'confirm' => 'Tasdiqlash',
        'confirm_text_default' => 'Siz bu amalni bajarishni xohlaysizmi?',
        'confirm_refresh_text' => 'Siz operatsiyani #:id yangilamoqchisiz. Davom etilsinmi?',
        'confirm_cancel_text' => 'Siz operatsiyani #:id bekor qilmoqchisiz. Davom etilsinmi?',
        'btn_search' => 'Qidirish',
        'showing' => 'Ko‘rsatilyapti',
        'total_groups' => 'Operatsiyalar soni',
        'total_messages' => 'Xabarlar soni',
        'refresh_success' => 'Operatsiya #:id yangilandi',
        'refresh_failed' => 'Operatsiya #:id ni yangilashda xatolik',
        'cancel_success' => 'Operatsiya #:id bekor qilindi. :count ta xabar holati o‘zgardi',
        'cancel_failed' => 'Operatsiya #:id ni bekor qilishda xatolik',
        'error_no_permission' => 'Sizda bu amalni bajarish ruxsati yo‘q',
    ],
    'login' => [
        'title' => 'Kirish',
        'welcome' => 'Xush kelibsiz',
        'subtitle' => 'Hisobingizga kiring',

        'email' => 'Email',
        'email_placeholder' => 'you@example.com',

        'password' => 'Parol',
        'password_placeholder' => '••••••••',

        'submit' => 'Kirish',

        'footer' => 'Postix AI',
    ],
    'users' => [
        'profile' => 'Profil',
        'title' => 'Foydalanuvchi',
        'refresh' => 'Yangilash',
        'delete_user' => 'Foydalanuvchini o‘chirish',
        'delete_confirm' => 'Haqiqatan o‘chirmoqchimisiz?',
        'user_updated' => 'Foydalanuvchi yangilandi',
        'user_deleted' => 'Foydalanuvchi o‘chirildi',
        'edit_user' => 'Foydalanuvchini tahrirlash',
        'name' => 'Ism',
        'email' => 'Email',
        'telegram_id' => 'Telegram ID',
        'role' => 'Rol',
        'no_role' => 'Rol yo‘q',
        'avatar' => 'Avatar',
        'remove_avatar' => 'Avatardan voz kechish',
        'phones' => 'Telefonlar',
        'add_phone' => 'Telefon qo‘shish',
        'add_phone_placeholder' => 'Telefon raqamini kiriting',
        'phone_added' => 'Telefon qo‘shildi',
        'phone_deleted' => 'Telefon o‘chirildi',
        'delete_phone' => 'O‘chirish',
        'phone_delete_confirm' => 'Telefonni o‘chirishni tasdiqlaysizmi?',
        'active' => 'Faol',
        'inactive' => 'Faol emas',
        'set_active' => 'Faollashtirish',
        'new_password' => 'Yangi parol',
        'leave_empty' => 'O‘zgarmasligi uchun bo‘sh qoldiring',
        'save_changes' => 'Saqlash',
        'no_email' => 'Email mavjud emas',
        'no_telegram' => 'Telegram mavjud emas',
        'no_change' => 'O‘zgarmasdan qoldirish',
        'back_to_list' => 'Orqaga',
        'phones_count' => 'telefonlar',
        'registered_at' => 'Ro\'yxatdan o\'tgan',
        'help_edit' => 'Profil ma\'lumotlarini yangilang',
        'manage_phones_hint' => 'Telefonlarni qo\'shish/ochirish',
        'add_phone_label' => 'Telefon qo\'shish',
        'send_sms' => 'Yuborish',
        'enter_code_label' => 'Kodni kiriting',
        'code_placeholder' => 'SMS kod',
        'verify_code' => 'Tasdiqlash',
        'change_phone' => 'Telefonni o\'zgartirish',
        'phone_required' => 'Telefon kiritilishi shart',
        'sms_sent' => 'SMS yuborildi',
        'sms_failed' => 'SMS yuborishda xatolik',
        'code_required' => 'Telefon va kod kiritilishi kerak',
        'verified' => 'Tasdiqlandi',
        'verify_failed' => 'Tasdiqlash muvaffaqiyatsiz',
        'operations_count' => 'Operatsiyalar soni',
        'messages_count' => 'Xabarlar soni',
        'search'=> 'Qidirish',
    ],





    'ban' => [
        'invalid_type' => 'Noto‘g‘ri ban turi.',
        'not_found' => ':model topilmadi.',
        'admin_department_forbidden' => 'Admin bo‘limni ban qila olmaydi.',
        'admin_to_admin_forbidden' => 'Admin boshqa adminni ban qila olmaydi.',
        'no_permission' => 'Bu amal uchun ruxsat yo‘q.',
        'invalid_date' => 'Sana formati noto‘g‘ri.',
        'unbanned' => ':model uchun ban olib tashlandi.',
        'banned_now' => ':model darhol ban qilindi.',
        'scheduled' => ':model uchun ban rejalashtirildi.',
        'internal_error' => 'Serverda ichki xatolik yuz berdi.',
    ],






    'telegram' => [

        'login' => 'Telegram bilan bog‘lash',
        'phone_label' => 'Telefon raqami',
        'phone_placeholder' => 'Foydalanuvchi telefon raqamini kiriting',
        'phone_required' => 'Telefon raqami kiritilishi shart',
        'send_sms' => 'SMS yuborish',
        'sms_sent' => 'Tasdiqlash kodi yuborildi',

        // Code step
        'code_label' => 'Tasdiqlash kodi',
        'code_placeholder' => 'SMS orqali kelgan kodni kiriting',
        'code_required' => 'Telefon va tasdiqlash kodi kiritilishi kerak',
        'send_code' => 'Kodni tasdiqlash',

        'verifying' => 'Tasdiqlanmoqda, iltimos kuting...',
        'verified' => 'Telegram muvaffaqiyatli bog‘landi',

        'invalid_code' => 'Tasdiqlash kodi noto‘g‘ri',
        'expired_code' => 'Tasdiqlash kodi muddati tugagan',
        'try_again' => 'Qayta urinib ko‘ring',
        'limit' => 'Limitga yetildi. Foydalanuvchi o‘chirilsa, yangi slot bo‘shaydi.',
        'user_exists' => 'Ushbu tizimda ushbu telefon raqamiga ega foydalanuvchi allaqachon mavjud.',
        'already_in_progress' => 'Ushbu telefon raqamiga ega foydalanuvchi bilan Telegram kirish jarayoni allaqachon davom etmoqda.',
        'started' => 'Telegram bilan bog‘lash jarayoni boshlandi. Iltimos, biroz kuting.',
    ],

];
