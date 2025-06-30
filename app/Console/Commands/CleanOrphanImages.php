<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOrphanImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-orphan-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Obtener todas las URLs de imágenes en uso
        $usedImages = collect();
        
        // Buscar en perfiles
        $usedImages = $usedImages->merge(
            UserProfile::whereNotNull('profile_picture')
                ->pluck('profile_picture')
        );
        
        // Buscar en productos (futuro)
        // $usedImages = $usedImages->merge(Product::pluck('image'));
        
        // 2. Obtener todas las imágenes en storage
        $allImages = Storage::disk('public')->allFiles('avatars');
        
        // 3. Encontrar huérfanas
        $orphanImages = collect($allImages)->filter(function($image) use ($usedImages) {
            $url = '/storage/' . $image;
            return !$usedImages->contains($url);
        });
        
        // 4. Eliminar huérfanas
        foreach($orphanImages as $image) {
            Storage::disk('public')->delete($image);
            $this->info("Eliminada: $image");
        }
    }
}
