<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class WishlistService
{
    private const SESSION_KEY =
        'storefront.wishlist';

    /**
     * Return authoritative wishlist product IDs.
     *
     * Guests:
     * - session-backed
     *
     * Authenticated customers:
     * - database-backed
     *
     * @return list<int>
     */
    public function ids(
        ?User $user = null,
    ): array {
        if ($user !== null) {
            return Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->orderBy('id')
                ->pluck('product_id')
                ->map(
                    static fn (
                        mixed $id,
                    ): int => (int) $id,
                )
                ->values()
                ->all();
        }

        return $this->guestIds();
    }

    public function count(
        ?User $user = null,
    ): int {
        if ($user !== null) {
            return Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count();
        }

        return count(
            $this->guestIds(),
        );
    }

    public function contains(
        Product $product,
        ?User $user = null,
    ): bool {
        if ($user !== null) {
            return Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'product_id',
                    $product->id,
                )
                ->exists();
        }

        return in_array(
            $product->id,
            $this->guestIds(),
            true,
        );
    }

    public function add(
        Product $product,
        ?User $user = null,
    ): void {
        if ($user !== null) {
            Wishlist::query()
                ->firstOrCreate([
                    'user_id' => $user->id,

                    'product_id' => $product->id,
                ]);

            return;
        }

        $ids =
            collect(
                $this->guestIds(),
            )
                ->push(
                    $product->id,
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
        ?User $user = null,
    ): void {
        if ($user !== null) {
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'product_id',
                    $product->id,
                )
                ->delete();

            return;
        }

        $ids =
            collect(
                $this->guestIds(),
            )
                ->reject(
                    static fn (
                        int $id,
                    ): bool => $id ===
                        $product->id,
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
        ?User $user = null,
    ): bool {
        if (
            $this->contains(
                product: $product,

                user: $user,
            )
        ) {
            $this->remove(
                product: $product,

                user: $user,
            );

            return false;
        }

        $this->add(
            product: $product,

            user: $user,
        );

        return true;
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(
        ?User $user = null,
    ): Collection {
        $ids =
            $this->ids(
                user: $user,
            );

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
                static fn (
                    Product $product,
                ): int => array_search(
                    $product->id,
                    $ids,
                    true,
                ),
            )
            ->values();
    }

    public function clear(
        ?User $user = null,
    ): void {
        if ($user !== null) {
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->delete();

            return;
        }

        $this->clearGuest();
    }

    /**
     * Merge the current guest session wishlist into the
     * authenticated customer's persistent wishlist.
     *
     * Existing DB entries are preserved.
     * Duplicate session IDs are ignored.
     * Invalid/missing products are ignored.
     *
     * @return int Number of new DB entries inserted.
     */
    public function mergeGuestInto(
        User $user,
    ): int {
        $guestIds =
            $this->guestIds();

        if ($guestIds === []) {
            return 0;
        }

        $validIds =
            Product::query()
                ->whereIn(
                    'id',
                    $guestIds,
                )
                ->pluck('id')
                ->map(
                    static fn (
                        mixed $id,
                    ): int => (int) $id,
                )
                ->values()
                ->all();

        if ($validIds === []) {
            $this->clearGuest();

            return 0;
        }

        $inserted =
            DB::transaction(
                function () use ($user, $validIds): int {
                    $now =
                        now();

                    $rows =
                        collect(
                            $validIds,
                        )
                            ->map(
                                static fn (
                                    int $productId,
                                ): array => [
                                    'user_id' => $user->id,

                                    'product_id' => $productId,

                                    'created_at' => $now,

                                    'updated_at' => $now,
                                ],
                            )
                            ->all();

                    return DB::table(
                        'wishlists',
                    )->insertOrIgnore(
                        $rows,
                    );
                },
            );

        /*
         * Clear only after successful database merge.
         */
        $this->clearGuest();

        return $inserted;
    }

    /**
     * @return list<int>
     */
    private function guestIds(): array
    {
        $ids =
            session()->get(
                self::SESSION_KEY,
                [],
            );

        if (! is_array($ids)) {
            return [];
        }

        return collect(
            $ids,
        )
            ->filter(
                static fn (
                    mixed $id,
                ): bool => is_numeric(
                    $id,
                ),
            )
            ->map(
                static fn (
                    mixed $id,
                ): int => (int) $id,
            )
            ->filter(
                static fn (
                    int $id,
                ): bool => $id > 0,
            )
            ->unique()
            ->values()
            ->all();
    }

    private function clearGuest(): void
    {
        session()->forget(
            self::SESSION_KEY,
        );
    }
}
