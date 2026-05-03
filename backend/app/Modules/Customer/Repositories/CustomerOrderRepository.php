<?php

namespace App\Modules\Customer\Repositories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerOrderRepository
{
    public function paginateForCustomer(User $customer, int $perPage = 15): LengthAwarePaginator
    {
        return $customer->orders()
            ->with(['items', 'payments'])
            ->latest()
            ->paginate($perPage);
    }

    public function findForCustomerOrFail(User $customer, int $id): Order
    {
        return $customer->orders()
            ->with(['items', 'payments'])
            ->findOrFail($id);
    }
}
