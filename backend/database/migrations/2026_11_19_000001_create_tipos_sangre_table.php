<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 3/10: tipos_sangre
 * Los 8 grupos sanguíneos (A+, A-, B+, B-, AB+, AB-, O+, O-).
 * La columna "puede_recibir_de" guarda la tabla de compatibilidad:
 * por ejemplo, A+ puede recibir de ["A+", "A-", "O+", "O-"].
 * Relaciones: 1 tipo de sangre tiene N donantes y N solicitudes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_sangre', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 3)->unique();          // "O-", "AB+", ...
            $table->enum('grupo', ['A', 'B', 'AB', 'O']);
            $table->enum('factor_rh', ['+', '-']);
            $table->json('puede_recibir_de');                // códigos de donantes compatibles
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_sangre');
    }
};
