<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductLocalModerationAnalyzer
{
    /**
     * @return array{
     *     fingerprint: string,
     *     decision: string,
     *     risk_score: float,
     *     findings: array<int, array{code: string, severity: string, message: string, blocking: bool}>
     * }
     */
    public function analyze(Product $product): array
    {
        $findings = [];
        $title = $this->normalize($product->name);
        $description = $this->normalize($product->desc ?? '');
        $content = trim($title.' '.$description);

        if ($this->containsContact($title)) {
            $findings[] = $this->finding(
                'contact_in_title',
                'medium',
                'Уберите телефон, ссылку или название мессенджера из названия товара.',
                true
            );
        }

        if ($this->looksLikeSpam($product->name)) {
            $findings[] = $this->finding(
                'spam',
                'medium',
                'Исправьте название: уберите повторяющиеся символы и чрезмерные заглавные буквы.',
                true
            );
        }

        if (mb_strlen(trim((string) $product->desc)) < 20) {
            $findings[] = $this->finding(
                'insufficient_description',
                'low',
                'Добавьте более подробное описание товара.',
                false
            );
        }

        foreach ($this->dangerousPatterns() as $code => $patterns) {
            if ($this->containsAny($content, $patterns)) {
                $findings[] = $this->finding(
                    $code,
                    'high',
                    'Обнаружены признаки категории, требующей усиленной ручной проверки.',
                    false
                );
            }
        }

        $hasHighRisk = collect($findings)->contains(
            fn (array $finding): bool => in_array($finding['severity'], ['high', 'critical'], true)
        );

        return [
            'fingerprint' => $this->fingerprint($product),
            'decision' => $hasHighRisk ? 'review' : 'allow',
            'risk_score' => $hasHighRisk ? 0.8 : (empty($findings) ? 0.0 : 0.25),
            'findings' => $findings,
        ];
    }

    public function fingerprint(Product $product): string
    {
        $imagePaths = $product->images()
            ->orderBy('order')
            ->pluck('path')
            ->map(fn (string $path): string => Str::lower(trim($path)))
            ->all();

        $payload = [
            'version' => $product->moderation_version,
            'name' => $this->normalize($product->name),
            'description' => $this->normalize($product->desc ?? ''),
            'features' => collect([
                $product->features1,
                $product->features2,
                $product->features3,
                $product->features4,
            ])->filter()->map(fn (string $value): string => $this->normalize($value))->values()->all(),
            'category_id' => $product->category_id,
            'price' => (string) $product->price,
            'lowprice' => $product->lowprice === null ? null : (string) $product->lowprice,
            'images' => $imagePaths,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function containsContact(string $title): bool
    {
        return preg_match('/(?:https?:\/\/|www\.|t\.me\/|telegram|whatsapp|viber|@\w{3,})/iu', $title) === 1
            || preg_match('/\+\d(?:[\s().-]*\d){6,14}(?!\d)/u', $title) === 1
            || preg_match('/(?<![\d.,])\d(?:[\s().-]*\d){7,14}(?![\d.,\p{L}])/u', $title) === 1;
    }

    private function looksLikeSpam(string $title): bool
    {
        if (preg_match('/(.)\1{4,}/u', $title) === 1) {
            return true;
        }

        $letters = preg_replace('/[^\p{L}]/u', '', $title) ?? '';

        return mb_strlen($letters) >= 10
            && mb_strtoupper($letters) === $letters;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function dangerousPatterns(): array
    {
        return [
            'weapons' => ['боеприпас', 'взрывчат', 'огнестрельн', 'пистолет без документов'],
            'illegal_drugs' => ['наркотик', 'закладк', 'психоактивн'],
            'documents_money' => ['поддельный паспорт', 'фальшивые деньги', 'купить документы'],
            'fraud_financial_scheme' => ['гарантированный заработок', 'финансовая пирамида', 'обнал без документов'],
        ];
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function containsAny(string $content, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($content, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->squish()->toString();
    }

    /**
     * @return array{code: string, severity: string, message: string, blocking: bool}
     */
    private function finding(
        string $code,
        string $severity,
        string $message,
        bool $blocking
    ): array {
        return compact('code', 'severity', 'message', 'blocking');
    }
}
