<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Image;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Probar crear una imagen sin campos polimórficos
$image1 = Image::create([
    'filename' => 'test1.jpg',
    'url' => '/storage/images/test1.jpg',
    'type' => 'avatar',
]);

echo "Imagen 1 creada: ID = " . $image1->id . "\n";
echo "imageable_id: " . ($image1->imageable_id ?? 'NULL') . "\n";
echo "imageable_type: " . ($image1->imageable_type ?? 'NULL') . "\n\n";

// Probar crear una imagen con campos polimórficos
$image2 = Image::create([
    'filename' => 'test2.jpg',
    'url' => '/storage/images/test2.jpg',
    'type' => 'product',
    'imageable_id' => 1,
    'imageable_type' => 'App\Models\User',
]);

echo "Imagen 2 creada: ID = " . $image2->id . "\n";
echo "imageable_id: " . $image2->imageable_id . "\n";
echo "imageable_type: " . $image2->imageable_type . "\n\n";

echo "¡Prueba exitosa! Los cambios funcionan correctamente.\n";
