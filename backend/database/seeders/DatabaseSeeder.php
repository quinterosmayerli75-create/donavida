<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Se ejecuta con: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,         // datos fijos del sistema
            TipoSangreSeeder::class,  // datos fijos del sistema
            DatosPruebaSeeder::class, // solo para desarrollo
        ]);
    }
}
