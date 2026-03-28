<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Delete duplicate earnings, keeping the most recently updated row per (symbol, date)
        DB::statement('
            DELETE FROM earnings
            WHERE id NOT IN (
                SELECT MAX(id)
                FROM earnings
                GROUP BY symbol, announcement_date
            )
        ');

        Schema::table('earnings', function (Blueprint $table) {
            $table->unique(['symbol', 'announcement_date'], 'earnings_symbol_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropUnique('earnings_symbol_date_unique');
        });
    }
};
