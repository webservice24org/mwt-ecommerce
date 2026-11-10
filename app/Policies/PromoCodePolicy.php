<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PromoCode;
use Illuminate\Contracts\Auth\Authenticatable;

final class PromoCodePolicy
{
    public function viewAny(
        Authenticatable $user,
    ): bool {
        return true;
    }

    public function view(
        Authenticatable $user,
        PromoCode $promoCode,
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
        PromoCode $promoCode,
    ): bool {
        return true;
    }

    public function delete(
        Authenticatable $user,
        PromoCode $promoCode,
    ): bool {
        return true;
    }
}
