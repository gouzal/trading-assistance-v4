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
        Schema::create('stock_prices', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->decimal('current_price', 15, 4)->nullable();
            $table->decimal('day_open', 15, 4)->nullable();
            $table->decimal('day_high', 15, 4)->nullable();
            $table->decimal('day_low', 15, 4)->nullable();
            $table->decimal('day_change', 15, 4)->nullable();
            $table->decimal('day_change_percent', 10, 4)->nullable();
            $table->bigInteger('volume')->nullable();
            $table->timestamp('last_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_prices');
    }
};
