<?php

namespace App\Models;

use Database\Factories\CameraEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraEvent extends Model
{
    /** @use HasFactory<CameraEventFactory> */
    use HasFactory;

    /** @var list<string> */
    public const TERMINAL_STATUSES = ['skipped', 'completed', 'failed'];

    protected $fillable = [
        'camera_id',
        'camera_name',
        'channel', 'rule', 'rule_title',
        'status',
        'recipients_total',
        'recipients_sent',
        'failure_type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => 'integer',
            'recipients_total' => 'integer',
            'recipients_sent' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Camera, $this> */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}
