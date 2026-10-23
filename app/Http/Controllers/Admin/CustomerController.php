<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Customer\Actions\BlockCustomerAction;
use App\Domain\Customer\Actions\RemoveCustomerAction;
use App\Domain\Customer\Actions\UnblockCustomerAction;
use App\Domain\Customer\Actions\UpdateCustomerAction;
use App\Domain\Customer\Data\CustomerIndexItemData;
use App\Domain\Customer\Enums\CustomerRemovalOutcome;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Domain\Customer\Queries\CustomerDetailQuery;
use App\Domain\Customer\Queries\CustomerIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Customer\BlockCustomerRequest;
use App\Http\Requests\Admin\Customer\CustomerIndexRequest;
use App\Http\Requests\Admin\Customer\DeleteCustomerRequest;
use App\Http\Requests\Admin\Customer\UnblockCustomerRequest;
use App\Http\Requests\Admin\Customer\UpdateCustomerRequest;
use App\Models\Address;
use App\Models\Admin;
use App\Models\CustomerStatusHistory;
use App\Models\Order;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerController extends Controller
{
    public function index(
        CustomerIndexRequest $request,
        CustomerIndexQuery $query,
    ): Response {
        $this->authorize(
            'viewAny',
            User::class,
        );

        $search = $request
            ->string('search')
            ->trim()
            ->toString();

        $statusValue = $request
            ->string('status')
            ->trim()
            ->toString();

        $status = $statusValue === ''
            ? null
            : CustomerStatus::from(
                $statusValue,
            );

        $verified = match (
            $request->query('verified')
        ) {
            '1' => true,
            '0' => false,
            default => null,
        };

        $customers = $query->paginate(
            search: $search === ''
                ? null
                : $search,
            status: $status,
            verified: $verified,
        );

        return Inertia::render(
            'Admin/Customers/Index',
            [
                'customers' => $customers->through(
                    static fn (
                        User $customer,
                    ): array => CustomerIndexItemData::fromModel(
                        $customer,
                    )->toArray(),
                ),

                'filters' => [
                    'search' => $search,
                    'status' => $statusValue,
                    'verified' => $request->query(
                        'verified',
                        '',
                    ),
                ],

                'options' => [
                    'statuses' => array_map(
                        static fn (
                            CustomerStatus $status,
                        ): array => [
                            'value' => $status->value,
                            'label' => Str::headline(
                                $status->value,
                            ),
                        ],
                        CustomerStatus::cases(),
                    ),
                ],
            ],
        );
    }

    public function show(
        User $customer,
        CustomerDetailQuery $query,
    ): Response {
        $this->authorize(
            'view',
            $customer,
        );

        $detail = $query->execute(
            $customer,
        );

        $customer = $detail[
            'customer'
        ];

        $orders = $detail[
            'orders'
        ];

        $statusHistory = $customer
            ->statusHistories()
            ->latest(
                'changed_at',
            )
            ->latest(
                'id',
            )
            ->limit(
                20,
            )
            ->get();

        return Inertia::render(
            'Admin/Customers/Show',
            [
                'customer' => [
                    ...CustomerIndexItemData::fromModel(
                        $customer,
                    )->toArray(),

                    'addresses_count' => (int) $customer
                        ->getAttribute(
                            'addresses_count',
                        ),

                    'anonymized' => $customer
                        ->isAnonymized(),

                    'anonymized_at' => self::dateToString(
                        $customer->getAttribute(
                            'anonymized_at',
                        ),
                    ),
                ],

                'addresses' => $detail[
                    'addresses'
                ]
                    ->map(
                        static fn (
                            Address $address,
                        ): array => [
                            'id' => $address->id,
                            'label' => $address->label,
                            'recipient_name' => $address
                                ->recipient_name,
                            'phone' => $address->phone,
                            'address_line_1' => $address
                                ->address_line_1,
                            'address_line_2' => $address
                                ->address_line_2,
                            'city' => $address->city,
                            'state_or_region' => $address
                                ->state_or_region,
                            'postal_code' => $address
                                ->postal_code,
                            'country_code' => $address
                                ->country_code,
                            'is_default' => (bool) $address
                                ->is_default,
                        ],
                    )
                    ->values()
                    ->all(),

                'orders' => $orders->through(
                    static fn (
                        Order $order,
                    ): array => [
                        'id' => $order->id,
                        'order_number' => $order
                            ->order_number,

                        'status' => [
                            'value' => $order
                                ->status
                                ->value,
                            'label' => $order
                                ->status
                                ->label(),
                        ],

                        'payment_status' => [
                            'value' => $order
                                ->payment_status
                                ->value,
                            'label' => $order
                                ->payment_status
                                ->label(),
                        ],

                        'fulfilment_status' => [
                            'value' => $order
                                ->fulfilment_status
                                ->value,
                            'label' => $order
                                ->fulfilment_status
                                ->label(),
                        ],

                        'currency_code' => $order
                            ->currency_code,
                        'grand_total' => $order
                            ->grand_total,
                        'placed_at' => $order
                            ->placed_at
                            ?->toISOString(),
                    ],
                ),

                'statusHistory' => $statusHistory
                    ->map(
                        static fn (
                            CustomerStatusHistory $history,
                        ): array => [
                            'id' => $history->id,
                            'from_status' => $history
                                ->from_status,
                            'from_status_label' => Str::headline(
                                $history->from_status,
                            ),
                            'to_status' => $history
                                ->to_status,
                            'to_status_label' => Str::headline(
                                $history->to_status,
                            ),
                            'reason' => $history
                                ->reason,
                            'actor_name' => $history
                                ->actor_name,
                            'changed_at' => self::dateToString(
                                $history->getAttribute(
                                    'changed_at',
                                ),
                            ),
                        ],
                    )
                    ->values()
                    ->all(),
            ],
        );
    }

    public function edit(
        User $customer,
    ): Response {
        $this->authorize(
            'update',
            $customer,
        );

        abort_if(
            $customer->isAnonymized(),
            404,
        );

        return Inertia::render(
            'Admin/Customers/Edit',
            [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'status' => $customer
                        ->status
                        ->value,
                    'status_label' => Str::headline(
                        $customer
                            ->status
                            ->value,
                    ),
                    'verified' => $customer
                        ->email_verified_at !== null,
                ],
            ],
        );
    }

    public function update(
        UpdateCustomerRequest $request,
        User $customer,
        UpdateCustomerAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $customer,
        );

        $customer = $action->execute(
            customer: $customer,
            data: $request->toData(),
        );

        return to_route(
            'admin.customers.show',
            $customer,
        )->with(
            'success',
            'Customer profile updated successfully.',
        );
    }

    public function block(
        BlockCustomerRequest $request,
        User $customer,
        BlockCustomerAction $action,
    ): RedirectResponse {
        $this->authorize(
            'block',
            $customer,
        );

        $admin = $request->user(
            'admin',
        );

        abort_unless(
            $admin instanceof Admin,
            403,
        );

        $action->execute(
            customer: $customer,
            actor: $admin,
            reason: $request->reason(),
        );

        return to_route(
            'admin.customers.show',
            $customer,
        )->with(
            'success',
            'Customer blocked successfully.',
        );
    }

    public function unblock(
        UnblockCustomerRequest $request,
        User $customer,
        UnblockCustomerAction $action,
    ): RedirectResponse {
        $this->authorize(
            'unblock',
            $customer,
        );

        $admin = $request->user(
            'admin',
        );

        abort_unless(
            $admin instanceof Admin,
            403,
        );

        $action->execute(
            customer: $customer,
            actor: $admin,
            reason: $request->reason(),
        );

        return to_route(
            'admin.customers.show',
            $customer,
        )->with(
            'success',
            'Customer unblocked successfully.',
        );
    }

    public function destroy(
        DeleteCustomerRequest $request,
        User $customer,
        RemoveCustomerAction $action,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $customer,
        );

        $admin = $request->user(
            'admin',
        );

        abort_unless(
            $admin instanceof Admin,
            403,
        );

        $outcome = $action->execute(
            customer: $customer,
            actor: $admin,
            reason: $request->reason(),
        );

        if (
            $outcome ===
            CustomerRemovalOutcome::Deleted
        ) {
            return to_route(
                'admin.customers.index',
            )->with(
                'success',
                'Customer deleted permanently because no transactional or audit history existed.',
            );
        }

        return to_route(
            'admin.customers.show',
            $customer->id,
        )->with(
            'success',
            'Customer personal account data was anonymized. Historical transactions were preserved.',
        );
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
