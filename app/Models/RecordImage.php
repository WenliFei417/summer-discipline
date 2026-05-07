<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordImage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'url',
        'path',
        'caption',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }
}
