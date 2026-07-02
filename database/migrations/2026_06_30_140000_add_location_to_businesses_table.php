<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubicación del local para las notificaciones por proximidad.
     *
     * Cuando el cliente se acerca al local, tanto Apple Wallet como Google Wallet
     * muestran el pase en la pantalla de bloqueo con un mensaje. iOS/Android
     * gestionan la geocerca de forma nativa: no hace falta enviar un push manual.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('address')->nullable()->after('description');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            // Radio de la geocerca en metros (solo Apple lo usa; Google es fijo ~150m).
            $table->unsignedSmallInteger('proximity_radius')->default(100)->after('longitude');
            // Texto que se muestra en la pantalla de bloqueo al estar cerca.
            $table->string('proximity_message')->nullable()->after('proximity_radius');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'latitude',
                'longitude',
                'proximity_radius',
                'proximity_message',
            ]);
        });
    }
};
