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
        Schema::create('trading_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 10);
            $table->string('order_type', 10); // buy, sell
            $table->string('order_class', 10); // market, limit
            $table->integer('quantity');
            $table->decimal('limit_price', 15, 4)->nullable();
            $table->decimal('executed_price', 15, 4)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('alpaca_order_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_orders');
    }
};
