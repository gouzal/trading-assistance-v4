<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider', 'endpoint', 'symbol', 'status', 'response_time_ms', 'error_message', 'created_at',
    ];

    public static function record(string $provider, string $endpoint, ?string $symbol, string $status, int $responseTimeMs, ?string $errorMessage = null): void
    {
        static::create([
            'provider'         => $provider,
            'endpoint'         => $endpoint,
            'symbol'           => $symbol,
            'status'           => $status,
            'response_time_ms' => $responseTimeMs,
            'error_message'    => $errorMessage,
            'created_at'       => now(),
        ]);
    }
}
