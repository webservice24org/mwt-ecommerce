<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

final class WishlistService
{
    private const SESSION_KEY = 'storefront.wishlist';

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        $ids = session()->get(
            self::SESSION_KEY,
            [],
        );

        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->filter(
                static fn (mixed $id): bool => is_numeric($id),
            )
            ->map(
                static fn (mixed $id): int => (int) $id,
            )
            ->filter(
                static fn (int $id): bool => $id > 0,
            )
            ->unique()
            ->values()
            ->all();
    }

    public function count(): int
    {
        return count(
            $this->ids(),
        );
    }

    public function contains(
        Product $product,
    ): bool {
        return in_array(
            $product->getKey(),
            $this->ids(),
            true,
        );
    }

    public function add(
        Product $product,
    ): void {
        $ids = collect(
            $this->ids(),
        )
            ->push(
                $product->getKey(),
            )
            ->unique()
            ->values()
            ->all();

        session()->put(
            self::SESSION_KEY,
            $ids,
        );
    }

    public function remove(
        Product $product,
    ): void {
        $ids = collect(
            $this->ids(),
        )
            ->reject(
                static fn (int $id): bool => $id ===
                    $product->getKey(),
            )
            ->values()
            ->all();

        session()->put(
            self::SESSION_KEY,
            $ids,
        );
    }

    public function toggle(
        Product $product,
    ): bool {
        if (
            $this->contains(
                $product,
            )
        ) {
            $this->remove(
                $product,
            );

            return false;
        }

        $this->add(
            $product,
        );

        return true;
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->published()
            ->whereIn(
                'id',
                $ids,
            )
            ->get()
            ->sortBy(
                static fn (Product $product): int => array_search(
                    $product->getKey(),
                    $ids,
                    true,
                ),
            )
            ->values();
    }

    public function clear(): void
    {
        session()->forget(
            self::SESSION_KEY,
        );
    }
}
