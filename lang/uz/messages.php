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
]
    
];
