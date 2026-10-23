<?php

declare(strict_types=1);

namespace App\Domain\Customer\Queries;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class CustomerIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(
        ?string $search = null,
        ?CustomerStatus $status = null,
        ?bool $verified = null,
    ): LengthAwarePaginator {
        $query = User::query()
            ->select([
                'id',
                'name',
                'email',
                'status',
                'email_verified_at',
                'created_at',
            ])
            ->withCount(
                'orders',
            );

        if (
            $search !== null
            && $search !== ''
        ) {
            $query->where(
                function (Builder $builder) use ($search): void {
                    $builder
                        ->where(
                            'name',
                            'like',
                            '%'.$search.'%',
                        )
                        ->orWhere(
                            'email',
                            'like',
                            '%'.$search.'%',
                        );
                },
            );
        }

        if ($status !== null) {
            $query->where(
                'status',
                $status->value,
            );
        }

        if ($verified === true) {
            $query->whereNotNull(
                'email_verified_at',
            );
        }

        if ($verified === false) {
            $query->whereNull(
                'email_verified_at',
            );
        }

        return $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }
}
