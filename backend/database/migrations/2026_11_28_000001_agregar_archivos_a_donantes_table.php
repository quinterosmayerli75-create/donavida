<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 1 (Mayerli): el donante puede subir su foto y, si ya donó antes,
 * una foto o PDF de su carnet de donante anterior.
 * - foto_ruta: disco "public" (se muestra en la app)
 * - carnet_ruta: disco "local" (privado, solo lo revisa el personal)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donantes', function (Blueprint $table) {
            $table->string('foto_ruta')->nullable()->after('disponible');
            $table->string('carnet_ruta')->nullable()->after('foto_ruta');
        });
    }

    public function down(): void
    {
        Schema::table('donantes', function (Blueprint $table) {
            $table->dropColumn(['foto_ruta', 'carnet_ruta']);
        });
    }
};
