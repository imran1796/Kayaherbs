<?php

namespace App\Modules\Customer\Services;

use App\Core\Services\AuditLogger;
use App\Core\Services\BaseService;
use App\Models\CustomerNote;
use App\Models\User;
use App\Modules\Customer\Repositories\CustomerSupportRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerSupportService extends BaseService
{
    public function __construct(
        private readonly CustomerSupportRepository $customers,
        private readonly AuditLogger $auditLogger
    ) {}

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->customers->paginate($perPage, $filters);
    }

    public function findForSupport(int $id): User
    {
        return $this->customers->findForSupport($id);
    }

    public function updateStatus(int $id, string $status, User $actor): User
    {
        return $this->transaction('customer.status.update', function () use ($id, $status, $actor): User {
            $customer = $this->customers->findCustomer($id);
            $previousStatus = $customer->status;
            $customer = $this->customers->updateStatus($customer, $status);

            $this->auditLogger->record('customer.status.updated', $actor, $customer, [
                'from_status' => $previousStatus,
                'to_status' => $status,
            ]);

            return $this->customers->findForSupport($customer->id);
        });
    }

    public function addNote(int $id, string $note, User $actor, array $metadata = []): CustomerNote
    {
        return $this->transaction('customer.note.create', function () use ($id, $note, $actor, $metadata): CustomerNote {
            $customer = $this->customers->findCustomer($id);
            $customerNote = $this->customers->createNote($customer, $actor, $note, $metadata);

            $this->auditLogger->record('customer.note.created', $actor, $customer, [
                'customer_note_id' => $customerNote->id,
            ]);

            return $customerNote;
        });
    }

    /**
     * @param  list<string>  $tags
     */
    public function syncTags(int $id, array $tags, User $actor): Collection
    {
        return $this->transaction('customer.tags.sync', function () use ($id, $tags, $actor): Collection {
            $customer = $this->customers->findCustomer($id);
            $syncedTags = $this->customers->syncTags($customer, $tags);

            $this->auditLogger->record('customer.tags.synced', $actor, $customer, [
                'tags' => $syncedTags->pluck('tag')->all(),
            ]);

            return $syncedTags;
        });
    }
}
