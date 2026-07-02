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
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            // Serial compartido por Apple y Google para identificar el pase
            $table->string('serial_number')->unique();
            // Token que Apple envía en cada llamada al web service (auth)
            $table->string('authentication_token');
            // Identificador del objeto en Google Wallet (issuerId.objectSuffix)
            $table->string('google_object_id')->nullable();
            // Se actualiza en cada cambio: Apple lo usa para detectar updates
            $table->timestamp('content_updated_at')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passes');
    }
};
