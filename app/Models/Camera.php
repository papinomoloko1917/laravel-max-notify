<?php

namespace App\Models;

use Database\Factories\CameraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Camera extends Model
{
    /** @use HasFactory<CameraFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'channel',
        'snapshot_base_url',
        'snapshot_username',
        'snapshot_password',
        'is_active',
        'webhook_username',
        'webhook_password',
        'webhook_token',
        'notify_from',
        'notify_until',
    ];

    protected $hidden = [
        'snapshot_password',
        'webhook_password',
        'webhook_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'channel' => 'integer',
            'snapshot_password' => 'encrypted',
            'webhook_password' => 'hashed',
            'webhook_token' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Camera $camera): void {
            $camera->webhook_token ??= Str::random(24);
        });
    }

    /** @return BelongsToMany<Client, $this> */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    /** @return HasMany<CameraEvent, $this> */
    public function cameraEvents(): HasMany
    {
        return $this->hasMany(CameraEvent::class);
    }

    /** @return HasMany<CameraRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(CameraRule::class);
    }
}
