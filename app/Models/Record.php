<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Record extends Model
{
    protected $fillable = [
        'record_date',
        'calendar_note',
        'ramblings',
        'health',
        'study',
    ];

    protected $casts = [
        'record_date' => 'date:Y-m-d',
        'health' => 'array',
        'study' => 'array',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(RecordImage::class);
    }
}
