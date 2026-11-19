<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 9/10: donaciones
 * Donación efectivamente realizada y validada por el hospital.
 * Relaciones: 1:1 con citas; N:1 con donantes.
 * El historial nunca se borra: si hubo un error se marca como "anulada".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->unique()->constrained('citas');
            $table->foreignId('donante_id')->constrained('donantes');
            $table->dateTime('fecha_donacion');
            $table->unsignedSmallInteger('volumen_ml')->default(450);
            $table->enum('estado', ['valida', 'anulada'])->default('valida');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donaciones');
    }
};
