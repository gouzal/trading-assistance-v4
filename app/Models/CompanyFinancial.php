<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFinancial extends Model
{
    protected $fillable = [
        'symbol', 'company_name', 'market_cap', 'current_price', 'pe_ratio', 'pb_ratio',
        'peg_ratio', 'debt_to_equity', 'profit_margin', 'revenue_estimate', 'revenue_turnover_pct',
        'fair_value_estimate', 'week_52_high', 'week_52_low', 'historic_high', 'historic_low',
        'data_provider', 'last_updated',
    ];

    protected $casts = ['last_updated' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'symbol', 'symbol');
    }
}
