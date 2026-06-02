<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InterventionImage;

/**
 * Сервис для работы с изображениями
 */
class ImageService
{
    private const THUMB_WIDTH = 300;

    private const THUMB_HEIGHT = 300;

    private const LARGE_WIDTH = 1200;

    private const LARGE_HEIGHT = 1200;

    /**
     * Загрузка и сохранение изображения
     */
    public function uploadImage(UploadedFile $file, string $disk = 'public'): Image
    {
        // Генерация уникального имени
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = 'products/'.date('Y/m');

        // Сохранение оригинала
        $originalPath = $file->storeAs($path, $filename, $disk);

        // Создание миниатюры
        $thumbPath = $this->createThumbnail($file, $path, 'thumb_'.$filename, $disk);

        // Создание большого изображения
        $largePath = $this->createResized($file, $path, 'large_'.$filename, $disk);

        // Создание записи в БД
        $image = new Image;
        $image->original_path = $originalPath;
        $image->thumb_path = $thumbPath;
        $image->large_path = $largePath;
        $image->size = $file->getSize();
        $image->mime_type = $file->getMimeType();
        $image->save();

        return $image;
    }

    /**
     * Прикрепление изображений к продукту
     */
    public function attachImages(Product $product, array $imageIds): void
    {
        $product->images()->attach($imageIds);
    }

    /**
     * Синхронизация изображений продукта
     */
    public function syncImages(Product $product, array $imageIds): void
    {
        $currentImageIds = $product->images()->pluck('images.id')->toArray();
        $toDelete = array_diff($currentImageIds, $imageIds);

        // Удаляем неиспользуемые изображения
        if (! empty($toDelete)) {
            $this->deleteImages($toDelete);
        }

        // Синхронизируем связи
        $product->images()->sync($imageIds);
    }

    /**
     * Установка главного изображения
     */
    public function setMainImage(Product $product, int $imageId): void
    {
        // Проверяем что изображение принадлежит продукту
        if (! $product->images()->where('images.id', $imageId)->exists()) {
            throw new \DomainException(__('errors.image_not_belongs_to_product'));
        }

        $product->main_image_id = $imageId;
        $product->save();
    }

    /**
     * Удаление изображений по ID
     */
    public function deleteImages(array $imageIds): void
    {
        $images = Image::whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            $this->deleteImageFiles($image);
            $image->delete();
        }
    }

    /**
     * Удаление всех изображений продукта
     */
    public function deleteAllImages(Product $product): void
    {
        $images = $product->images;

        foreach ($images as $image) {
            $this->deleteImageFiles($image);
        }

        $product->images()->detach();
        Image::whereIn('id', $images->pluck('id'))->delete();
    }

    /**
     * Создание миниатюры
     */
    private function createThumbnail(
        UploadedFile $file,
        string $path,
        string $filename,
        string $disk
    ): string {
        $image = InterventionImage::make($file);
        $image->fit(self::THUMB_WIDTH, self::THUMB_HEIGHT);

        $fullPath = storage_path('app/'.$disk.'/'.$path.'/'.$filename);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $image->save($fullPath);

        return $path.'/'.$filename;
    }

    /**
     * Создание большого изображения с ограничением размера
     */
    private function createResized(
        UploadedFile $file,
        string $path,
        string $filename,
        string $disk
    ): string {
        $image = InterventionImage::make($file);

        // Ограничиваем размер сохраняя пропорции
        $image->resize(self::LARGE_WIDTH, self::LARGE_HEIGHT, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $fullPath = storage_path('app/'.$disk.'/'.$path.'/'.$filename);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $image->save($fullPath);

        return $path.'/'.$filename;
    }

    /**
     * Удаление файлов изображения с диска
     */
    private function deleteImageFiles(Image $image, string $disk = 'public'): void
    {
        if ($image->original_path) {
            Storage::disk($disk)->delete($image->original_path);
        }
        if ($image->thumb_path) {
            Storage::disk($disk)->delete($image->thumb_path);
        }
        if ($image->large_path) {
            Storage::disk($disk)->delete($image->large_path);
        }
    }
}
