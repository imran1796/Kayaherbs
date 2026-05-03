<?php

namespace App\Modules\Customer\Services;

use App\Models\Order;
use App\Models\User;
use App\Modules\Customer\Repositories\CustomerOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerOrderService
{
    public function __construct(
        private readonly CustomerOrderRepository $orders
    ) {}

    public function paginate(User $customer, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orders->paginateForCustomer($customer, $perPage);
    }

    public function findOrFail(User $customer, int $id): Order
    {
        return $this->orders->findForCustomerOrFail($customer, $id);
    }
}
