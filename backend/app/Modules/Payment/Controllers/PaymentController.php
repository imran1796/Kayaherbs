<?php

namespace App\Modules\Payment\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Payment\Requests\CollectCodPaymentRequest;
use App\Modules\Payment\Requests\UpdatePaymentStatusRequest;
use App\Modules\Payment\Resources\PaymentResource;
use App\Modules\Payment\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function updateStatus(UpdatePaymentStatusRequest $request, int $id)
    {
        $payment = $this->paymentService->transitionStatusById(
            $id,
            (string) $request->validated('status'),
            $request->user('sanctum') ?? $request->user(),
            $request->validated('metadata', []),
            $request->validated('transaction_id'),
            $request->validated('provider_reference')
        );

        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment status updated successfully.'
        );
    }

    public function collectCod(CollectCodPaymentRequest $request, int $id)
    {
        $payment = $this->paymentService->markCodCollectedById(
            $id,
            $request->user('sanctum') ?? $request->user(),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new PaymentResource($payment),
            'COD payment collected successfully.'
        );
    }
}
