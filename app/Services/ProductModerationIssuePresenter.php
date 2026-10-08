<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Замечания проверки к товару в виде, готовом для показа продавцу.
 * Нейросеть часто присылает одно и то же замечание несколько раз (например, и по фото, и по названию):
 * такие замечания склеиваются в одно, чтобы продавец не читал один текст дважды.
 */
class ProductModerationIssuePresenter
{
    /**
     * @return array<int, array{
     *     field: string, label: string, target: string, message: string, suggestion: ?string,
     *     is_image_issue: bool, image_index: ?int, image_url: ?string,
     *     fields: array<int, array{field: string, label: string, target: string}>,
     *     images: array<int, array{index: int, url: string}>
     * }>
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

        $issues = collect($run?->reasons ?? [])
            ->filter(fn (mixed $issue): bool => is_array($issue))
            ->map(function (array $issue) use ($images): array {
                $field = $this->normalizeField($issue['field'] ?? null);
                $isImageIssue = $field === 'images';
                $image = $isImageIssue ? $this->imageForIssue($images, $issue) : null;

                return [
                    'field' => $field,
                    'label' => $this->fieldLabel($field),
                    'target' => $this->fieldTarget($field),
                    'message' => trim((string) ($issue['evidence'] ?? $issue['message'] ?? '')),
                    'suggestion' => filled($issue['suggestion'] ?? null)
                        ? trim((string) $issue['suggestion'])
                        : null,
                    'is_image_issue' => $isImageIssue,
                    // номер фото относится только к замечаниям по фото: у «Названия» бейдж «Фото №1» только путает
                    'image_index' => $isImageIssue
                        ? ($image['index'] ?? $this->normalizeImageIndex($issue['image_index'] ?? null))
                        : null,
                    'image_url' => $image['url'] ?? null,
                ];
            })
            ->filter(fn (array $issue): bool => $issue['message'] !== '');

        return $this->merge($issues)
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Замечания с одинаковым текстом превращаются в одно: поля собираются в список, фото — в набор миниатюр,
     * рекомендации — без повторов (и без пересказа самого замечания).
     *
     * @param  Collection<int, array<string, mixed>>  $issues
     * @return Collection<int, array<string, mixed>>
     */
    private function merge(Collection $issues): Collection
    {
        return $issues
            ->groupBy(fn (array $issue): string => $this->fingerprint($issue['message']))
            ->map(function (Collection $same, string $messageKey): array {
                $first = $same->first();
                $imageIssue = $same->first(fn (array $issue): bool => $issue['is_image_issue']);
                $primary = $imageIssue ?? $first;

                $suggestions = $same
                    ->pluck('suggestion')
                    ->filter()
                    ->unique(fn (string $suggestion): string => $this->fingerprint($suggestion))
                    ->reject(fn (string $suggestion): bool => $this->fingerprint($suggestion) === $messageKey)
                    ->values();

                return [
                    'field' => $first['field'],
                    'label' => $first['label'],
                    'target' => $first['target'],
                    'message' => $first['message'],
                    'suggestion' => $suggestions->isEmpty() ? null : $suggestions->implode(' '),
                    'is_image_issue' => $imageIssue !== null,
                    'image_index' => $primary['image_index'],
                    'image_url' => $primary['image_url'],
                    'fields' => $same
                        ->unique('field')
                        ->map(fn (array $issue): array => [
                            'field' => $issue['field'],
                            'label' => $issue['label'],
                            'target' => $issue['target'],
                        ])
                        ->values()
                        ->all(),
                    'images' => $same
                        ->filter(fn (array $issue): bool => $issue['image_index'] !== null)
                        ->unique('image_index')
                        ->map(fn (array $issue): array => [
                            'index' => $issue['image_index'],
                            'url' => $issue['image_url'],
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();
    }

    /** Текст без регистра, лишних пробелов и знаков в конце: так находятся «одинаковые» замечания. */
    private function fingerprint(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return rtrim($text, " .,;:!?…\"'«»");
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
