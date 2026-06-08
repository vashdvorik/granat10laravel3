<?php

namespace App\Services;

use App\Models\Image as ProductImage;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class ProductImageService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeUploadedImages(Product $product, array $files, bool $makeFirstMain = false): void
    {
        if (! $this->canAddImages($product, count($files))) {
            throw ValidationException::withMessages([
                'images' => 'Превышен лимит изображений. Можно загрузить ещё '
                    .$this->remainingImageSlots($product)
                    .' фото.',
            ]);
        }

        $nextOrder = ((int) $product->images()->max('order')) + 1;

        foreach ($files as $index => $file) {
            $fullPath = $this->storeUploadedFile($file);

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $fullPath,
                'is_main' => $makeFirstMain && $index === 0,
                'order' => $nextOrder + $index,
            ]);
        }
    }

    public function deleteProductFiles(Product $product): void
    {
        foreach ($product->images as $image) {
            $this->deleteFile($image);
        }
    }

    public function deleteImage(ProductImage $image): void
    {
        $product = $image->product;
        $wasMain = $image->is_main;

        $this->deleteFile($image);
        $image->delete();

        if ($wasMain && $product) {
            $this->ensureMainImage($product);
        }
    }

    public function setMainImage(ProductImage $image): void
    {
        $product = $image->product;

        $product->images()->update(['is_main' => false]);
        $image->update(['is_main' => true]);
    }

    public function canAddImages(Product $product, int $newFilesCount, int $limit = 5): bool
    {
        return $product->images()->count() + $newFilesCount <= $limit;
    }

    public function remainingImageSlots(Product $product, int $limit = 5): int
    {
        return max(0, $limit - $product->images()->count());
    }

    private function storeUploadedFile(UploadedFile $file): string
    {
        $filename = Str::uuid().'.webp';
        $path = 'products/'.date('Y/m');

        $image = Image::make($file)->orientate();

        $image->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('webp', 85);

        $image->fit(800, 800, function ($constraint) {
            $constraint->upsize();
        })->encode('webp', 85);

        $fullPath = $path.'/'.$filename;
        Storage::disk('public')->put($fullPath, (string) $image);

        return $fullPath;
    }

    private function deleteFile(ProductImage $image): void
    {
        if (Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }
    }

    private function ensureMainImage(Product $product): void
    {
        $firstImage = $product->images()->first();

        if ($firstImage) {
            $firstImage->update(['is_main' => true]);
        }
    }
}
