<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use App\Domain\Seo\Merchant\Services\MerchantOrganizationPolicyStructuredDataResolver;
use App\Domain\Seo\Merchant\Services\MerchantStructuredDataPolicyService;
use App\Http\Requests\Admin\Seo\UpdateMerchantStructuredDataPolicyRequest;
use App\Models\MerchantStructuredDataPolicyEvent;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class MerchantStructuredDataPolicyController
{
    public function index(
        MerchantStructuredDataPolicyService $policies,
        MerchantOrganizationPolicyStructuredDataResolver $resolver,
    ): Response {
        $policy =
            $policies->get();

        return Inertia::render(
            'Admin/Seo/Merchant/Policies',
            [
                'policy' => $policy->toArray(),

                'structuredDataPreview' => $resolver->resolve(),

                'shippingOmissionReasons' => $resolver
                    ->shippingOmissionReasons(),

                'history' => MerchantStructuredDataPolicyEvent::query()
                    ->orderByDesc(
                        'id',
                    )
                    ->limit(
                        20,
                    )
                    ->get()
                    ->map(
                        static fn (
                            MerchantStructuredDataPolicyEvent $event,
                        ): array => [
                            'id' => $event->id,

                            'version' => $event->version,

                            'after_policy' => $event
                                ->after_policy,

                            'changed_by_admin_id' => $event
                                ->changed_by_admin_id,

                            'created_at' => $event
                                ->created_at
                                ->toIso8601String(),
                        ],
                    )
                    ->values()
                    ->all(),

                'options' => [
                    'return_categories' => array_map(
                        static fn (
                            MerchantReturnPolicyCategory $value,
                        ): array => [
                            'value' => $value
                                ->value,

                            'label' => match (
                                $value
                            ) {
                                MerchantReturnPolicyCategory::Finite => 'Finite return window',

                                MerchantReturnPolicyCategory::NotPermitted => 'Returns not permitted',

                                MerchantReturnPolicyCategory::Unlimited => 'Unlimited return window',
                            },
                        ],
                        MerchantReturnPolicyCategory::cases(),
                    ),

                    'return_methods' => array_map(
                        static fn (
                            MerchantReturnMethod $value,
                        ): array => [
                            'value' => $value
                                ->value,

                            'label' => match (
                                $value
                            ) {
                                MerchantReturnMethod::ByMail => 'Return by mail',

                                MerchantReturnMethod::InStore => 'Return in store',

                                MerchantReturnMethod::AtKiosk => 'Return at kiosk',
                            },
                        ],
                        MerchantReturnMethod::cases(),
                    ),

                    'return_fees' => array_map(
                        static fn (
                            MerchantReturnFees $value,
                        ): array => [
                            'value' => $value
                                ->value,

                            'label' => match (
                                $value
                            ) {
                                MerchantReturnFees::Free => 'Free returns',

                                MerchantReturnFees::CustomerResponsibility => 'Customer pays return shipping',

                                MerchantReturnFees::ShippingFee => 'Merchant charges fixed return shipping fee',
                            },
                        ],
                        MerchantReturnFees::cases(),
                    ),

                    'refund_types' => array_map(
                        static fn (
                            MerchantRefundType $value,
                        ): array => [
                            'value' => $value
                                ->value,

                            'label' => match (
                                $value
                            ) {
                                MerchantRefundType::Full => 'Full refund',

                                MerchantRefundType::Exchange => 'Exchange',

                                MerchantRefundType::StoreCredit => 'Store credit',
                            },
                        ],
                        MerchantRefundType::cases(),
                    ),
                ],
            ],
        );
    }

    /**
     * @throws ValidationException
     */
    public function update(
        UpdateMerchantStructuredDataPolicyRequest $request,
        MerchantStructuredDataPolicyService $policies,
    ): RedirectResponse {
        try {
            $policies->save(
                data: $request->toData(),

                expectedVersion: $request->expectedVersion(),

                changedByAdminId: $this->actorId(
                    $request,
                ),
            );
        } catch (
            DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'version' => $exception
                    ->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'Merchant structured-data policy updated.',
        );
    }

    private function actorId(
        Request $request,
    ): ?int {
        $identifier =
            $request
                ->user(
                    'admin',
                )
                ?->getAuthIdentifier();

        if (is_int($identifier)) {
            return $identifier;
        }

        if (
            is_string(
                $identifier,
            )
            && ctype_digit(
                $identifier,
            )
        ) {
            return (int) $identifier;
        }

        return null;
    }
}
