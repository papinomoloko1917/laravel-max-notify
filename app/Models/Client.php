<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'max_chat_id',
    ];

    protected function casts(): array
    {
        return [
            'max_chat_id' => 'integer',
        ];
    }

    /** @return BelongsToMany<Camera, $this> */
    public function cameras(): BelongsToMany
    {
        return $this->belongsToMany(Camera::class)->withTimestamps();
    }
}
