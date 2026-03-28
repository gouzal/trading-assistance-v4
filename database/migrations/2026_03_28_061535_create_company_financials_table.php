<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_financials', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->string('company_name')->nullable();
            $table->decimal('market_cap', 20, 2)->nullable();
            $table->decimal('current_price', 15, 4)->nullable();
            $table->decimal('pe_ratio', 10, 2)->nullable();
            $table->decimal('pb_ratio', 10, 2)->nullable();
            $table->decimal('peg_ratio', 10, 2)->nullable();
            $table->decimal('debt_to_equity', 10, 2)->nullable();
            $table->decimal('profit_margin', 10, 4)->nullable();
            $table->decimal('revenue_estimate', 15, 2)->nullable();
            $table->decimal('revenue_turnover_pct', 10, 2)->nullable();
            $table->decimal('fair_value_estimate', 15, 4)->nullable();
            $table->decimal('week_52_high', 15, 4)->nullable();
            $table->decimal('week_52_low', 15, 4)->nullable();
            $table->decimal('historic_high', 15, 4)->nullable();
            $table->decimal('historic_low', 15, 4)->nullable();
            $table->string('data_provider', 20)->default('finnhub');
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_financials');
    }
};
