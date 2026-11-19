<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 8/10: citas
 * Compromiso de un donante con UNA solicitud o UNA campaña, con horario elegido.
 * Relaciones: N:1 con donantes; N:1 con solicitudes o con campanas; 1:1 con donaciones.
 *
 * Regla de negocio (la valida el backend ANTES de crear la cita):
 * el donante debe tener al menos 3 meses desde su última donación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donante_id')->constrained('donantes');
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes');
            $table->foreignId('campana_id')->nullable()->constrained('campanas');
            $table->dateTime('fecha_hora');
            $table->enum('estado', ['pendiente', 'confirmada', 'asistio', 'no_asistio', 'cancelada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // La cita debe pertenecer a una solicitud O a una campaña, pero no a las dos ni a ninguna.
        // Funciona en MySQL 8 y en MariaDB (XAMPP).
        $motorBaseDatos = DB::getDriverName();
        if ($motorBaseDatos === 'mysql' || $motorBaseDatos === 'mariadb') {
            DB::statement('ALTER TABLE citas ADD CONSTRAINT chk_citas_origen CHECK (
                (solicitud_id IS NOT NULL AND campana_id IS NULL)
                OR (solicitud_id IS NULL AND campana_id IS NOT NULL)
            )');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
