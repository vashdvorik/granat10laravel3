<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductModerationRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationRunDetailsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_shows_image_moderation_details(): void
    {
        $product = Product::factory()->create();
        $run = ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 1,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'model' => 'gemini-2.5-flash',
            'status' => 'completed',
            'decision' => 'review',
            'request_fingerprint' => hash('sha256', 'view-details'),
            'reasons' => [[
                'code' => 'wrong_image',
                'field' => 'images',
                'image_index' => 1,
                'evidence' => 'Фото не относится к товару.',
                'suggestion' => 'Замените фото.',
            ]],
            'image_result' => [
                'images_submitted' => 1,
            ],
        ]);

        $html = $this->blade(
            '@include("filament.resources.product-resource.moderation-run-details", ["run" => $run])',
            ['run' => $run]
        );

        $html->assertSee('Причины решения');
        $html->assertSee('wrong_image');
        $html->assertSee('фото №1');
        $html->assertSee('Фото не относится к товару.');
        $html->assertSee('Данные проверки фото');
        $html->assertSee('images_submitted');
    }
}
