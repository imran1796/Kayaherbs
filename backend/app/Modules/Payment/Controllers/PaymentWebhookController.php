<?php

namespace App\Modules\Payment\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Payment\Resources\PaymentWebhookLogResource;
use App\Modules\Payment\Services\PaymentWebhookService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentWebhookService $webhooks
    ) {}

    public function handle(Request $request, string $provider)
    {
        $result = $this->webhooks->handle(
            $provider,
            $this->headers($request),
            $request->getContent()
        );

        if ($result['log']->status === 'rejected') {
            return ApiResponse::error(
                'Webhook signature is invalid.',
                401,
                ['signature' => ['Webhook signature is invalid.']],
                'invalid_webhook_signature'
            );
        }

        return ApiResponse::success(
            new PaymentWebhookLogResource($result['log']),
            $result['duplicate'] ? 'Webhook already processed.' : 'Webhook processed successfully.',
            200,
            ['duplicate' => $result['duplicate']]
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        return collect($request->headers->all())
            ->mapWithKeys(fn (array $value, string $key): array => [strtolower($key) => (string) ($value[0] ?? '')])
            ->all();
    }
}
