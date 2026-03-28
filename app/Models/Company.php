<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'symbol', 'name', 'sector', 'industry', 'country',
        'logo_url', 'is_favorite', 'notes', 'added_by',
    ];

    protected $casts = ['is_favorite' => 'boolean'];

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class, 'symbol', 'symbol');
    }

    public function financial(): HasOne
    {
        return $this->hasOne(CompanyFinancial::class, 'symbol', 'symbol');
    }

    public function sentiment(): HasOne
    {
        return $this->hasOne(Sentiment::class, 'symbol', 'symbol')->latestOfMany();
    }

    public function stockPrice(): HasOne
    {
        return $this->hasOne(StockPrice::class, 'symbol', 'symbol');
    }
}
