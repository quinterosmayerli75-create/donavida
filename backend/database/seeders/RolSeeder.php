<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => Rol::ADMINISTRADOR, 'descripcion' => 'Valida hospitales y supervisa todo el sistema'],
            ['nombre' => Rol::HOSPITAL, 'descripcion' => 'Publica solicitudes y campañas, registra donaciones'],
            ['nombre' => Rol::DONANTE, 'descripcion' => 'Recibe avisos, acepta solicitudes y agenda citas'],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(['nombre' => $rol['nombre']], $rol);
        }
    }
}
