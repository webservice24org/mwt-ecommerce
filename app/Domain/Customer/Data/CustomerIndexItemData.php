<?php

declare(strict_types=1);

namespace App\Domain\Customer\Data;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Str;

final readonly class CustomerIndexItemData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $status,
        public string $statusLabel,
        public bool $verified,
        public ?string $emailVerifiedAt,
        public int $ordersCount,
        public ?string $createdAt,
        public bool $anonymized,
        public ?string $anonymizedAt,
    ) {}

    public static function fromModel(
        User $user,
    ): self {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status->value,
            statusLabel: $user->anonymized_at !== null
            ? 'Anonymized'
            : Str::headline(
                $user->status->value,
            ),
            verified: $user->email_verified_at !== null,
            emailVerifiedAt: self::dateToString(
                $user->getAttribute(
                    'email_verified_at',
                ),
            ),
            ordersCount: (int) $user->getAttribute(
                'orders_count',
            ),
            createdAt: self::dateToString(
                $user->getAttribute(
                    'created_at',
                ),
            ),
            anonymized: $user->anonymized_at !== null,

            anonymizedAt: self::dateToString(
                $user->getAttribute(
                    'anonymized_at',
                ),
            ),
        );
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     status: string,
     *     status_label: string,
     *     verified: bool,
     *     email_verified_at: string|null,
     *     orders_count: int,
     *     created_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'verified' => $this->verified,
            'email_verified_at' => $this->emailVerifiedAt,
            'orders_count' => $this->ordersCount,
            'created_at' => $this->createdAt,
            'anonymized' => $this->anonymized,
            'anonymized_at' => $this->anonymizedAt,
        ];
    }

    private static function dateToString(
        mixed $value,
    ): ?string {
        if ($value instanceof DateTimeInterface) {
            return $value->format(
                DATE_ATOM,
            );
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }
}
