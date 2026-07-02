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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('description')->nullable();
            // Colores del pase (formato "rgb(r,g,b)" o "#hex")
            $table->string('background_color')->default('rgb(26,26,26)');
            $table->string('foreground_color')->default('rgb(255,255,255)');
            $table->string('label_color')->default('rgb(180,180,180)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
