<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name'        => 'Barbería El Corte',
                'description' => 'Cortes y barba de primera. Tu barbería de confianza.',
            ]
        );

        $demo = [
            ['name' => 'Carlos Méndez', 'phone' => '809-555-0101', 'tier' => 'vip',       'points' => 120],
            ['name' => 'Luis Pérez',    'phone' => '809-555-0102', 'tier' => 'frecuente', 'points' => 45],
            ['name' => 'Andrés Gómez',  'phone' => '809-555-0103', 'tier' => 'nuevo',     'points' => 0],
        ];

        foreach ($demo as $data) {
            $client = $business->clients()->firstOrCreate(['phone' => $data['phone']], $data);
            $client->pass()->firstOrCreate([]); // genera serial + token
        }
    }
}
