<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraRule extends Model
{
    protected $fillable = [
        'code',
        'title',
    ];

    /** @return BelongsTo<Camera, $this> */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}
