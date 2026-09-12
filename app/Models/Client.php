<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'max_chat_id',
    ];

    protected function casts(): array
    {
        return [
            'max_chat_id' => 'integer',
        ];
    }
}
