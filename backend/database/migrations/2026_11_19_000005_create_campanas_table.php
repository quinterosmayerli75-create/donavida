<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 7/10: campanas  (se escribe sin "ñ" para evitar problemas en código y URLs)
 * Jornadas programadas de donación: fecha, lugar y cupos.
 * Relaciones: N:1 con hospitales; 1:N con citas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitales');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('lugar', 200);
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('cupos');
            $table->enum('estado', ['programada', 'en_curso', 'finalizada', 'cancelada'])->default('programada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanas');
    }
};
