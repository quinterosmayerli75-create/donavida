<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 4/10: donantes
 * Datos personales, ubicación, disponibilidad y fecha de la última donación.
 * Relaciones: 1:1 con usuarios; N:1 con tipos_sangre; 1:N con citas y donaciones.
 *
 * Reglas que valida el backend (no la base de datos):
 *  - Edad entre 18 y 60 años (Ley 1687, art. 19) usando fecha_nacimiento.
 *  - Al menos 3 meses entre donaciones usando fecha_ultima_donacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios');
            $table->foreignId('tipo_sangre_id')->constrained('tipos_sangre');
            $table->string('apellidos', 100);
            $table->string('ci', 20)->unique();
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['F', 'M']);
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('zona', 100)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->boolean('disponible')->default(true);
            $table->date('fecha_ultima_donacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donantes');
    }
};
