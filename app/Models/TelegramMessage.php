<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'message_group_id',
        'telegram_message_id',
        'peer',
        'message_text',
        'send_at',
        'sent_at',
        'status',
        'attempts'
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(MessageGroup::class, 'message_group_id');
    }
    
}
    

