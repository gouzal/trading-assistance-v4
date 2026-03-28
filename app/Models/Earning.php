<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    protected $fillable = [
        'symbol', 'announcement_date', 'announcement_time',
        'estimated_revenue', 'actual_revenue', 'estimated_eps', 'actual_eps', 'api_data',
    ];

    protected $casts = [
        'announcement_date' => 'date',
        'api_data' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'symbol', 'symbol');
    }
}
