<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla 2/10: usuarios
 * Credenciales de acceso, teléfono y estado de la cuenta.
 * Relaciones: N usuarios pertenecen a 1 rol; 1 usuario puede ser 1 donante o 1 hospital.
 *
 * Este archivo también crea dos tablas internas de Laravel
 * (password_reset_tokens y sessions) que no cuentan dentro de las 10 tablas del modelo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles');
            $table->string('nombre', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('password');
            // Eliminación lógica: nunca se borra un usuario, solo cambia su estado.
            $table->enum('estado', ['activo', 'inactivo', 'pendiente'])->default('activo');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
