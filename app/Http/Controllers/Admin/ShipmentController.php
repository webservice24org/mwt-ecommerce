<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shipping\Actions\AutoSubmitShipmentIfEnabledAction;
use App\Domain\Shipping\Actions\CancelShipmentAction;
use App\Domain\Shipping\Actions\CreateShipmentAction;
use App\Domain\Shipping\Actions\DeliverShipmentAction;
use App\Domain\Shipping\Actions\QueueShipmentProviderTrackingSyncAction;
use App\Domain\Shipping\Actions\ResolveShipmentProviderReviewAction;
use App\Domain\Shipping\Actions\ReturnShipmentAction;
use App\Domain\Shipping\Actions\ShipShipmentAction;
use App\Domain\Shipping\Actions\StartShipmentProcessingAction;
use App\Domain\Shipping\Actions\SubmitShipmentToProviderAction;
use App\Domain\Shipping\Actions\UpdateShipmentTrackingAction;
use App\Domain\Shipping\Data\CreateShipmentItemData;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\CancelShipmentRequest;
use App\Http\Requests\Admin\Orders\CreateShipmentRequest;
use App\Http\Requests\Admin\Orders\ProviderShipmentActionRequest;
use App\Http\Requests\Admin\Orders\ResolveShipmentProviderReviewRequest;
use App\Http\Requests\Admin\Orders\TransitionShipmentRequest;
use App\Http\Requests\Admin\Orders\UpdateShipmentTrackingRequest;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ShipmentController extends Controller
{
    public function store(
        CreateShipmentRequest $request,
        Order $order,
        CreateShipmentAction $action,
        AutoSubmitShipmentIfEnabledAction $autoSubmit,
    ): RedirectResponse {
        $shippingMethod = ShippingMethod::query()
            ->findOrFail(
                $request->shippingMethodId(),
            );

        $items = array_map(
            static fn (
                array $item,
            ): CreateShipmentItemData => new CreateShipmentItemData(
                orderItemId: $item['order_item_id'],

                quantity: $item['quantity'],
            ),
            $request->shipmentItems(),
        );

        /*
         * CreateShipmentAction owns the shipment creation
         * transaction. It must finish before any external
         * courier API call is attempted.
         */
        $shipment = $action->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: $items,

            carrier: $request->carrier(),

            service: $request->service(),

            providerCode: $request->providerCode(),
        );

        try {
            $submission = $autoSubmit->execute(
                $shipment,
            );
        } catch (ProviderSubmissionOutcomeUnknownException $exception) {
            Log::warning(
                'Automatic shipping provider submission outcome requires manual review.',
                [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'exception_type' => $exception::class,
                ],
            );

            return $this
                ->redirectToOrder(
                    order: $order,

                    message: 'Shipment created, but automatic provider submission requires manual review.',
                )
                ->withErrors([
                    'provider' => 'Provider submission outcome is uncertain. The shipment was saved; check the provider panel before retrying.',
                ]);
        } catch (ValidationException $exception) {
            /*
             * Shipment creation has already committed. Preserve it
             * and return the provider-safe validation message rather
             * than pretending the shipment was never created.
             */
            return $this
                ->redirectToOrder(
                    order: $order,

                    message: 'Shipment created, but automatic provider submission was not completed.',
                )
                ->withErrors(
                    $exception->errors(),
                );
        } catch (Throwable $exception) {
            /*
             * Never expose raw transport/provider exception text.
             * SubmitShipmentToProviderAction has already persisted
             * the appropriate Failed/Review Required submission state.
             */
            Log::warning(
                'Automatic shipping provider submission failed after shipment creation.',
                [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'exception_type' => $exception::class,
                ],
            );

            return $this
                ->redirectToOrder(
                    order: $order,

                    message: 'Shipment created, but automatic provider submission failed.',
                )
                ->withErrors([
                    'provider' => 'Automatic provider submission failed. The shipment remains saved and can be reviewed from this order.',
                ]);
        }

        return $this->redirectToOrder(
            order: $order,

            message: $submission !== null
                ? 'Shipment created and automatically submitted to the courier provider.'
                : 'Shipment created successfully.',
        );
    }

    public function submitProvider(
        ProviderShipmentActionRequest $request,
        Order $order,
        Shipment $shipment,
        SubmitShipmentToProviderAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        try {
            $action->execute(
                $shipment,
            );
        } catch (ProviderSubmissionOutcomeUnknownException $exception) {
            Log::warning(
                'Shipping provider submission outcome requires manual review.',
                [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'exception_type' => $exception::class,
                ],
            );

            return $this
                ->redirectToOrder(
                    order: $order,

                    message: 'Provider submission requires manual review.',
                )
                ->withErrors([
                    'provider' => 'Provider submission outcome is uncertain. Check the provider panel before retrying.',
                ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            /*
             * Never report raw provider exceptions here. HTTP client
             * exceptions may contain URLs or transport diagnostics.
             * Persisted domain errors are already sanitized.
             */
            Log::warning(
                'Shipping provider submission failed.',
                [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'exception_type' => $exception::class,
                ],
            );

            return $this
                ->redirectToOrder(
                    order: $order,

                    message: 'Provider shipment submission failed.',
                )
                ->withErrors([
                    'provider' => 'Provider shipment submission failed.',
                ]);
        }

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment submitted to the shipping provider.',
        );
    }

    public function syncProvider(
        ProviderShipmentActionRequest $request,
        Order $order,
        Shipment $shipment,
        QueueShipmentProviderTrackingSyncAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $action->execute(
            $shipment,
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Provider tracking synchronization queued.',
        );
    }

    public function resolveProviderReview(
        ResolveShipmentProviderReviewRequest $request,
        Order $order,
        Shipment $shipment,
        ResolveShipmentProviderReviewAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $resolution = $request->resolution();

        $action->execute(
            shipment: $shipment,

            resolution: $resolution,

            externalReference: $request->externalReference(),

            note: $request->note(),

            actor: $this->admin(
                $request,
            ),
        );

        return $this->redirectToOrder(
            order: $order,

            message: $resolution->value === 'confirm_existing'
            ? 'Existing provider shipment confirmed. You can now synchronize tracking.'
            : 'Provider review resolved. Submission retry is now permitted.',
        );
    }

    public function startProcessing(
        TransitionShipmentRequest $request,
        Order $order,
        Shipment $shipment,
        StartShipmentProcessingAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $action->execute(
            shipment: $shipment,

            actor: $this->admin(
                $request,
            ),

            note: $request->note(),
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment processing started.',
        );
    }

    public function updateTracking(
        UpdateShipmentTrackingRequest $request,
        Order $order,
        Shipment $shipment,
        UpdateShipmentTrackingAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $action->execute(
            shipment: $shipment,

            carrier: $request->carrier(),

            service: $request->service(),

            trackingNumber: $request->trackingNumber(),

            trackingUrl: $request->trackingUrl(),

            actor: $this->admin(
                $request,
            ),
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment tracking details updated.',
        );
    }

    public function ship(
        TransitionShipmentRequest $request,
        Order $order,
        Shipment $shipment,
        ShipShipmentAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $providerCode =
            $shipment->effectiveProviderCode();

        if (
            (
                $providerCode === null
                ||
                $providerCode ===
                ManualShippingProvider::CODE
            )
            &&
            (
                ! is_string(
                    $shipment->carrier,
                )
                ||
                trim(
                    $shipment->carrier,
                ) === ''
            )
        ) {
            throw ValidationException::withMessages([
                'carrier' => 'Set a carrier before marking a manual shipment as shipped.',
            ]);
        }

        $action->execute(
            shipment: $shipment,

            actor: $this->admin(
                $request,
            ),

            note: $request->note(),
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment marked as shipped.',
        );
    }

    public function deliver(
        TransitionShipmentRequest $request,
        Order $order,
        Shipment $shipment,
        DeliverShipmentAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $action->execute(
            shipment: $shipment,

            actor: $this->admin(
                $request,
            ),

            note: $request->note(),
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment marked as delivered.',
        );
    }

    public function cancel(
        CancelShipmentRequest $request,
        Order $order,
        Shipment $shipment,
        CancelShipmentAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $action->execute(
            shipment: $shipment,

            actor: $this->admin(
                $request,
            ),

            reason: $request->reason(),
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment cancelled successfully.',
        );
    }

    public function returnShipment(
        TransitionShipmentRequest $request,
        Order $order,
        Shipment $shipment,
        ReturnShipmentAction $action,
    ): RedirectResponse {
        $shipment = $this->shipmentForOrder(
            order: $order,

            shipment: $shipment,
        );

        $action->execute(
            shipment: $shipment,

            actor: $this->admin(
                $request,
            ),

            note: $request->note(),
        );

        return $this->redirectToOrder(
            order: $order,

            message: 'Shipment marked as returned.',
        );
    }

    private function shipmentForOrder(
        Order $order,
        Shipment $shipment,
    ): Shipment {
        if (
            $shipment->order_id !==
            $order->id
        ) {
            abort(
                404,
            );
        }

        return $shipment;
    }

    private function admin(
        FormRequest $request,
    ): Admin {
        $admin = $request->user(
            'admin',
        );

        if (! $admin instanceof Admin) {
            abort(
                403,
            );
        }

        return $admin;
    }

    private function redirectToOrder(
        Order $order,
        string $message,
    ): RedirectResponse {
        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            $message,
        );
    }
}
