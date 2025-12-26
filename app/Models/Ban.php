<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    protected $fillable = [
        'bannable_type',
        'bannable_id',
        'reason',
        'active',
        'until'
    ];
    public function bannable()
    {
        return $this->morphTo();
    }
}
