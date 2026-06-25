<?php

namespace App\Services;

use App\Data\ModerationImage;
use Illuminate\Support\Facades\Storage;

class ProductModerationImagePreparer
{
    /**
     * @param  array<int, string>  $paths
     * @return array<int, ModerationImage>
     */
    public function prepare(array $paths): array
    {
        if (! config('moderation.vision.enabled', true)) {
            return [];
        }

        $disk = Storage::disk('public');
        $images = [];
        $maxImages = max(0, (int) config('moderation.vision.max_images', 5));
        $maxBytes = max(0, (int) config('moderation.vision.max_image_bytes', 2_000_000));

        foreach (array_slice($paths, 0, $maxImages) as $path) {
            if (! $disk->exists($path) || $disk->size($path) > $maxBytes) {
                continue;
            }

            $mimeType = $disk->mimeType($path);

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                continue;
            }

            $contents = $disk->get($path);

            if ($contents === '') {
                continue;
            }

            $images[] = new ModerationImage($mimeType, base64_encode($contents));
        }

        return $images;
    }
}
