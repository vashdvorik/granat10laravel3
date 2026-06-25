<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductModerationIssuePresenter
{
    /**
     * @return array<int, array{field: string, label: string, target: string, message: string, suggestion: ?string}>
     */
    public function forProduct(Product $product): array
    {
        $run = $product->moderationRuns()
            ->where('content_version', $product->moderation_version)
            ->where('status', 'completed')
            ->whereNotNull('reasons')
            ->latest('id')
            ->first();

        $images = $this->productImages($product);

        return collect($run?->reasons ?? [])
            ->filter(fn (mixed $issue): bool => is_array($issue))
            ->map(function (array $issue) use ($images): array {
                $field = $this->normalizeField($issue['field'] ?? null);
                $image = $field === 'images'
                    ? $this->imageForIssue($images, $issue)
                    : null;

                return [
                    'field' => $field,
                    'label' => $this->fieldLabel($field),
                    'target' => $this->fieldTarget($field),
                    'message' => trim((string) ($issue['evidence'] ?? $issue['message'] ?? '')),
                    'suggestion' => filled($issue['suggestion'] ?? null)
                        ? trim((string) $issue['suggestion'])
                        : null,
                    'is_image_issue' => $field === 'images',
                    'image_index' => $image['index'] ?? $this->normalizeImageIndex($issue['image_index'] ?? null),
                    'image_url' => $image['url'] ?? null,
                ];
            })
            ->filter(fn (array $issue): bool => $issue['message'] !== '')
            ->take(10)
            ->values()
            ->all();
    }

    private function normalizeField(mixed $field): string
    {
        return in_array($field, ['name', 'description', 'features', 'category', 'images'], true)
            ? $field
            : 'general';
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'name' => 'Название',
            'description' => 'Описание',
            'features' => 'Характеристики',
            'category' => 'Категория',
            'images' => 'Фотографии',
            default => 'Карточка товара',
        };
    }

    private function fieldTarget(string $field): string
    {
        return match ($field) {
            'name' => 'name',
            'description' => 'desc',
            'features' => 'features1',
            'category' => 'category-selectors',
            'images' => 'product-images-section',
            default => 'product-edit-form',
        };
    }

    /**
     * @return array<int, array{index: int, url: string}>
     */
    private function productImages(Product $product): array
    {
        return $product->images()
            ->orderByDesc('is_main')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(fn ($image, int $index): array => [
                'index' => $index + 1,
                'url' => Storage::disk('public')->url($image->path),
            ])
            ->all();
    }

    /**
     * @param  array<int, array{index: int, url: string}>  $images
     * @return array{index: int, url: string}|null
     */
    private function imageForIssue(array $images, array $issue): ?array
    {
        $index = $this->normalizeImageIndex($issue['image_index'] ?? null);

        if ($index === null) {
            return null;
        }

        return collect($images)->firstWhere('index', $index);
    }

    private function normalizeImageIndex(mixed $index): ?int
    {
        if (is_numeric($index) && (int) $index > 0) {
            return (int) $index;
        }

        return null;
    }
}
