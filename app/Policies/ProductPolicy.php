<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasPermission('browse_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function view(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function activate(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function deactivate(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function manageImages(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function pushUp(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function ownsProduct(User $user, Product $product): bool
    {
        return $product->seller?->user_id === $user->id;
    }
}
