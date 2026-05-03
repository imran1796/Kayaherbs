<?php

namespace App\Modules\Payment\Repositories;

use App\Models\PaymentWebhookLog;

class PaymentWebhookRepository
{
    public function findDuplicate(string $provider, ?string $eventId, string $payloadHash): ?PaymentWebhookLog
    {
        if ($eventId !== null) {
            $log = PaymentWebhookLog::query()
                ->where('provider', $provider)
                ->where('event_id', $eventId)
                ->first();

            if ($log !== null) {
                return $log;
            }
        }

        /** @var PaymentWebhookLog|null $log */
        return PaymentWebhookLog::query()
            ->where('provider', $provider)
            ->where('payload_hash', $payloadHash)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentWebhookLog
    {
        /** @var PaymentWebhookLog $log */
        $log = PaymentWebhookLog::query()->create($data);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PaymentWebhookLog $log, array $data): PaymentWebhookLog
    {
        $log->update($data);

        return $log->refresh();
    }
}
