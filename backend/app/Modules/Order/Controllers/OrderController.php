<?php

namespace App\Modules\Order\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\Order\Requests\CancelOrderRequest;
use App\Modules\Order\Requests\GenerateInvoiceRequest;
use App\Modules\Order\Requests\GeneratePackingSlipRequest;
use App\Modules\Order\Requests\PublicOrderLookupRequest;
use App\Modules\Order\Requests\StoreOrderNoteRequest;
use App\Modules\Order\Requests\StoreReturnRequest;
use App\Modules\Order\Requests\StoreShipmentRequest;
use App\Modules\Order\Requests\UpdateOrderStatusRequest;
use App\Modules\Order\Resources\AdminOrderResource;
use App\Modules\Order\Resources\OrderInvoiceResource;
use App\Modules\Order\Resources\OrderNoteResource;
use App\Modules\Order\Resources\OrderPackingSlipResource;
use App\Modules\Order\Resources\OrderReturnRequestResource;
use App\Modules\Order\Resources\OrderShipmentResource;
use App\Modules\Order\Services\OrderLifecycleService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderLifecycleService $orders
    ) {}

    public function lookup(PublicOrderLookupRequest $request)
    {
        $phone = $request->validated('phone');

        $order = Order::query()
            ->with(['customer', 'items', 'payments', 'statusHistories'])
            ->where('order_number', $request->validated('order_number'))
            ->when($phone !== '', function ($query) use ($phone): void {
                $query->where(function ($phoneQuery) use ($phone): void {
                    $phoneQuery
                        ->whereHas('customer', fn ($customerQuery) => $customerQuery->where('phone', $phone))
                        ->orWhere('shipping_address->phone', $phone);
                });
            })
            ->firstOrFail();

        return ApiResponse::success(
            new AdminOrderResource($order),
            'Order fetched successfully.'
        );
    }

    public function index(Request $request)
    {
        $orders = $this->orders->paginate(
            (int) $request->integer('per_page', 15),
            $request->only(['search', 'status', 'payment_status', 'fulfillment_status'])
        );

        return ApiResponse::success(
            AdminOrderResource::collection($orders),
            'Orders fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new AdminOrderResource($this->orders->findOrFail($id)),
            'Order fetched successfully.'
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id)
    {
        $order = $this->orders->transitionStatus(
            $this->orders->findOrFail($id),
            (string) $request->validated('status'),
            $request->user('sanctum') ?? $request->user(),
            $request->validated('note'),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new AdminOrderResource($order),
            'Order status updated successfully.'
        );
    }

    public function storeNote(StoreOrderNoteRequest $request, int $id)
    {
        $note = $this->orders->addInternalNote(
            $this->orders->findOrFail($id),
            (string) $request->validated('note'),
            $request->user('sanctum'),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new OrderNoteResource($note),
            'Order note added successfully.',
            201
        );
    }

    public function cancel(CancelOrderRequest $request, int $id)
    {
        $order = $this->orders->cancel(
            $this->orders->findOrFail($id),
            (string) $request->validated('reason'),
            $request->user('sanctum') ?? $request->user(),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new AdminOrderResource($order),
            'Order cancelled successfully.'
        );
    }

    public function storeReturnRequest(StoreReturnRequest $request, int $id)
    {
        $returnRequest = $this->orders->requestReturn(
            $this->orders->findOrFail($id),
            (string) $request->validated('reason'),
            $request->user('sanctum') ?? $request->user(),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new OrderReturnRequestResource($returnRequest),
            'Return request created successfully.',
            201
        );
    }

    public function generateInvoice(GenerateInvoiceRequest $request, int $id)
    {
        $invoice = $this->orders->generateInvoice(
            $this->orders->findOrFail($id),
            $request->user('sanctum') ?? $request->user(),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new OrderInvoiceResource($invoice),
            'Invoice generated successfully.',
            $invoice->wasRecentlyCreated ? 201 : 200
        );
    }

    public function generatePackingSlip(GeneratePackingSlipRequest $request, int $id)
    {
        $packingSlip = $this->orders->generatePackingSlip(
            $this->orders->findOrFail($id),
            $request->user('sanctum') ?? $request->user(),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new OrderPackingSlipResource($packingSlip),
            'Packing slip generated successfully.',
            $packingSlip->wasRecentlyCreated ? 201 : 200
        );
    }

    public function storeShipment(StoreShipmentRequest $request, int $id)
    {
        $shipment = $this->orders->createShipment(
            $this->orders->findOrFail($id),
            $request->validated(),
            $request->user('sanctum') ?? $request->user()
        );

        return ApiResponse::success(
            new OrderShipmentResource($shipment),
            'Shipment linked successfully.',
            201
        );
    }
}
