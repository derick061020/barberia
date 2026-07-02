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
        Schema::create('pass_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pass_id')->constrained()->cascadeOnDelete();
            // Identificador del dispositivo que envía Apple al registrarse
            $table->string('device_library_identifier');
            // Token APNs para enviar la notificación push de actualización
            $table->string('push_token');
            $table->timestamps();

            $table->unique(['device_library_identifier', 'pass_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pass_devices');
    }
};
