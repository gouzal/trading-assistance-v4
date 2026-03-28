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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->string('name');
            $table->string('sector', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('country', 10)->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->text('notes')->nullable();
            $table->string('added_by', 20)->default('system');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
