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
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10);
            $table->date('announcement_date');
            $table->string('announcement_time', 10)->nullable(); // BMO, AMC, DMH
            $table->decimal('estimated_revenue', 15, 2)->nullable();
            $table->decimal('actual_revenue', 15, 2)->nullable();
            $table->decimal('estimated_eps', 10, 4)->nullable();
            $table->decimal('actual_eps', 10, 4)->nullable();
            $table->json('api_data')->nullable();
            $table->timestamps();
            $table->foreign('symbol')->references('symbol')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
};
