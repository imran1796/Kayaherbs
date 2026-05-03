<?php

namespace App\Modules\Customer\Repositories;

use App\Models\CustomerNote;
use App\Models\CustomerTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerSupportRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->customers()
            ->withCount('orders')
            ->latest();

        $this->applyFilters($query, $filters);

        return $query
            ->paginate($perPage);
    }

    public function findForSupport(int $id): User
    {
        $customer = User::query()
            ->customers()
            ->withCount('orders')
            ->with([
                'customerAddresses',
                'customerNotes.author',
                'customerTags',
            ])
            ->findOrFail($id);

        $recentOrders = $customer->orders()
            ->with(['items', 'payments'])
            ->latest()
            ->limit(25)
            ->get();

        return $customer->setRelation('orders', $recentOrders);
    }

    public function findCustomer(int $id): User
    {
        return User::query()
            ->customers()
            ->findOrFail($id);
    }

    public function updateStatus(User $customer, string $status): User
    {
        $customer->update(['status' => $status]);

        return $customer->refresh();
    }

    public function createNote(User $customer, User $author, string $note, array $metadata = []): CustomerNote
    {
        return CustomerNote::query()
            ->create([
                'user_id' => $customer->id,
                'author_id' => $author->id,
                'note' => $note,
                'metadata' => $metadata,
            ])
            ->load('author');
    }

    /**
     * @param  list<string>  $tags
     * @return Collection<int, CustomerTag>
     */
    public function syncTags(User $customer, array $tags): Collection
    {
        $tags = collect($tags)
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->unique()
            ->values();

        CustomerTag::query()
            ->where('user_id', $customer->id)
            ->whereNotIn('tag', $tags->all())
            ->delete();

        foreach ($tags as $tag) {
            CustomerTag::query()->firstOrCreate([
                'user_id' => $customer->id,
                'tag' => $tag,
            ]);
        }

        return CustomerTag::query()
            ->where('user_id', $customer->id)
            ->orderBy('tag')
            ->get();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }
    }
}
