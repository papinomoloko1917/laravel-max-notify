<?php

namespace App\Models;

use Database\Factories\CameraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Camera extends Model
{
    /** @use HasFactory<CameraFactory> */
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'webhook_username', 'webhook_password', 'notify_from', 'notify_until'];

    protected $hidden = ['webhook_password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'webhook_password' => 'hashed',
        ];
    }

    /** @return BelongsToMany<Client, $this> */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class)->withTimestamps();
    }
}
