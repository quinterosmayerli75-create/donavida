<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 5/10: hospitales
 * Razón social, dirección, coordenadas y contacto.
 * "verificado" lo activa el administrador al validar el hospital.
 * Relaciones: 1:1 con usuarios; 1:N con solicitudes y campanas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios');
            $table->string('razon_social', 150);
            $table->string('nit', 20)->unique();
            $table->string('direccion', 200);
            $table->string('zona', 100)->nullable();
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->string('telefono_contacto', 20);
            $table->string('whatsapp', 20)->nullable();   // para el botón "Abrir WhatsApp"
            $table->boolean('verificado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitales');
    }
};
