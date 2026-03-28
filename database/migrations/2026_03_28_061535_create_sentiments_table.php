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
        Schema::create('sentiments', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10);
            $table->decimal('sentiment_score', 5, 4)->nullable();
            $table->string('sentiment_label', 20)->nullable();
            $table->string('analyst_rating', 50)->nullable();
            $table->integer('buy_count')->nullable();
            $table->integer('hold_count')->nullable();
            $table->integer('sell_count')->nullable();
            $table->json('news_data')->nullable();
            $table->boolean('revised_expectations')->default(false);
            $table->string('revision_direction', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentiments');
    }
};
