<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Promotion;
use Illuminate\Contracts\Auth\Authenticatable;

final class PromotionPolicy
{
    public function viewAny(
        Authenticatable $user,
    ): bool {
        return true;
    }

    public function view(
        Authenticatable $user,
        Promotion $promotion,
    ): bool {
        return true;
    }

    public function create(
        Authenticatable $user,
    ): bool {
        return true;
    }

    public function update(
        Authenticatable $user,
        Promotion $promotion,
    ): bool {
        return true;
    }

    public function delete(
        Authenticatable $user,
        Promotion $promotion,
    ): bool {
        return true;
    }
}
