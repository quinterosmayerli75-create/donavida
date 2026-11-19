<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 10/10: notificaciones
 * Avisos enviados al usuario (la "campanita") y su estado de lectura.
 * Relaciones: N:1 con usuarios; N:1 con solicitudes (opcional).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes');
            $table->enum('tipo', ['solicitud_urgente', 'cita', 'recordatorio', 'sistema']);
            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_lectura')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
