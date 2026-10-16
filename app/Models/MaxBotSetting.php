<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaxBotSetting extends Model
{
    public const int CURRENT_ID = 1;

    protected $fillable = [
        'id',
        'token',
        'bot_user_id',
        'bot_name',
        'bot_username',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'bot_user_id' => 'integer',
        ];
    }
}
