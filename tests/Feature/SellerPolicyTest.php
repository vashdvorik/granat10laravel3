<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SellerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_is_allowed_all_seller_abilities(): void
    {
        [$owner, $seller] = $this->createOwnedSeller();

        foreach ($this->abilities() as $ability) {
            $this->assertTrue(
                Gate::forUser($owner)->allows($ability, $seller),
                "Owner should be allowed to {$ability} seller."
            );
        }
    }

    public function test_other_user_is_denied_all_seller_abilities(): void
    {
        [, $seller] = $this->createOwnedSeller();
        $otherUser = User::factory()->create();

        foreach ($this->abilities() as $ability) {
            $this->assertFalse(
                Gate::forUser($otherUser)->allows($ability, $seller),
                "Other user should not be allowed to {$ability} seller."
            );
        }
    }

    public function test_backend_seller_routes_forbid_non_owner(): void
    {
        [, $seller] = $this->createOwnedSeller();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $this->get(route('account.store.edit', $seller))->assertForbidden();
        $this->put(route('account.store.update', $seller), [
            'name' => 'Foreign Seller Update',
        ])->assertForbidden();
    }

    private function createOwnedSeller(): array
    {
        $owner = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $owner->id]);

        return [$owner, $seller];
    }

    private function abilities(): array
    {
        return [
            'view',
            'update',
            'delete',
            'manageSettings',
            'purchasePackage',
        ];
    }
}
