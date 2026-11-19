<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 6/10: solicitudes
 * Requerimiento urgente de sangre publicado por un hospital.
 * Relaciones: N:1 con hospitales y tipos_sangre; 1:N con citas y notificaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitales');
            $table->foreignId('tipo_sangre_id')->constrained('tipos_sangre');
            $table->unsignedSmallInteger('unidades_requeridas');
            $table->unsignedSmallInteger('unidades_confirmadas')->default(0);
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('alta');
            $table->text('motivo')->nullable();
            $table->dateTime('fecha_limite');
            $table->enum('estado', ['abierta', 'cubierta', 'cerrada', 'cancelada'])->default('abierta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
