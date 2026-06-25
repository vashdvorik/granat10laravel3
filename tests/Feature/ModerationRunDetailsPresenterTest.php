<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ModerationRunDetailsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationRunDetailsPresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_presenter_formats_image_moderation_run_details(): void
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
            'risk_score' => 0.8,
            'request_fingerprint' => hash('sha256', 'details'),
            'provider_request_id' => 'request-123',
            'reasons' => [[
                'code' => 'wrong_image',
                'field' => 'images',
                'image_index' => 2,
                'evidence' => 'Фото не относится к товару.',
                'suggestion' => 'Замените фото.',
            ]],
            'image_result' => [
                'policy_version' => 'marketplace-image-policy-v1',
                'images_submitted' => 3,
            ],
        ]);

        $details = app(ModerationRunDetailsPresenter::class)->present($run);

        $this->assertSame('image_google', $details['summary'][0]['value']);
        $this->assertSame('wrong_image', $details['reasons'][0]['code']);
        $this->assertSame(2, $details['reasons'][0]['image_index']);
        $this->assertSame(3, $details['imageResult']['images_submitted']);
    }
}
