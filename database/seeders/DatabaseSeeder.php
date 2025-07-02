<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador principal
        $admin = User::create([
            'name' => 'Arturo Castillo',
            'email' => 'arturog.castillo@gmail.com',
            'role' => 'admin',
            'password' => Hash::make('artcast12'),
        ]);

        // Crear perfil para el admin
        $admin->profile()->create([
            'first_name' => 'Arturo',
            'last_name' => 'Castillo',
            'phone' => '+52 555 1234567',
            'bio' => 'Administrador del sistema',
            'birth_date' => '1990-01-15',
            'preferences' => [
                'theme' => 'dark',
                'language' => 'es',
                'notifications' => true
            ],
        ]);

        // Crear dirección para el admin
        $admin->addresses()->create([
            'street' => 'Av. Revolución',
            'street_number' => '1234',
            'apartment' => 'Depto 5A',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'postal_code' => '06700',
            'country' => 'México',
            'additional_info' => 'Entre Calle Insurgentes y Av. Chapultepec',
            'is_default' => true,
        ]);

        // Crear usuarios de prueba con factory
        User::factory(5)->create()->each(function ($user) {
            // Crear perfil para cada usuario
            $user->profile()->create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->phoneNumber(),
                'bio' => fake()->sentence(10),
                'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'preferences' => [
                    'theme' => fake()->randomElement(['light', 'dark']),
                    'language' => fake()->randomElement(['es', 'en']),
                    'notifications' => fake()->boolean(),
                ],
            ]);

            // Crear 1-2 direcciones para cada usuario
            $addressCount = fake()->numberBetween(1, 2);
            for ($i = 0; $i < $addressCount; $i++) {
                $user->addresses()->create([
                    'street' => fake()->streetName(),
                    'street_number' => fake()->buildingNumber(),
                    'apartment' => $i === 0 ? null : fake()->randomElement([null, 'Depto ' . fake()->bothify('##?')]),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'postal_code' => fake()->postcode(),
                    'country' => 'México',
                    'additional_info' => fake()->optional()->sentence(),
                    'is_default' => $i === 0, // Primera dirección es por defecto
                ]);
            }
        });

        // Crear usuario staff específico
        $staff = User::create([
            'name' => 'Juan Pérez',
            'email' => 'staff@example.com',
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);

        $staff->profile()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone' => '+52 555 9876543',
            'bio' => 'Personal de soporte técnico',
            'birth_date' => '1985-06-20',
            'preferences' => [
                'theme' => 'light',
                'language' => 'es',
                'notifications' => true
            ],
        ]);

        $staff->addresses()->create([
            'street' => 'Calle Morelos',
            'street_number' => '456',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'postal_code' => '44100',
            'country' => 'México',
            'is_default' => true,
        ]);

        // Crear usuario regular específico
        $user = User::create([
            'name' => 'María González',
            'email' => 'user@example.com',
            'role' => 'user',
            'password' => Hash::make('password123'),
        ]);

        $user->profile()->create([
            'first_name' => 'María',
            'last_name' => 'González',
            'phone' => '+52 555 5551234',
            'bio' => 'Usuario regular del sistema',
            'birth_date' => '1992-03-10',
            'preferences' => [
                'theme' => 'dark',
                'language' => 'es',
                'notifications' => false
            ],
        ]);

        $user->addresses()->create([
            'street' => 'Av. Juárez',
            'street_number' => '789',
            'apartment' => 'Piso 3',
            'city' => 'Monterrey',
            'state' => 'Nuevo León',
            'postal_code' => '64000',
            'country' => 'México',
            'additional_info' => 'Cerca del centro comercial',
            'is_default' => true,
        ]);
    }
}
