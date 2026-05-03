<?php

namespace App\Modules\Customer\Repositories;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CustomerAddressRepository
{
    public function listForCustomer(User $customer): Collection
    {
        return $customer->customerAddresses()
            ->latest()
            ->get();
    }

    public function findCustomerOrFail(int $customerId): User
    {
        return User::query()
            ->customers()
            ->findOrFail($customerId);
    }

    public function findForCustomerOrFail(User $customer, int $id): CustomerAddress
    {
        return $customer->customerAddresses()
            ->whereKey($id)
            ->firstOrFail();
    }

    public function create(User $customer, array $data): CustomerAddress
    {
        return $customer->customerAddresses()
            ->create($data)
            ->refresh();
    }

    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        $address->update($data);

        return $address->refresh();
    }

    public function delete(CustomerAddress $address): void
    {
        $address->delete();
    }

    public function clearDefault(User $customer, string $field, ?int $exceptId = null): void
    {
        $addresses = $customer->customerAddresses()
            ->where($field, true);

        if ($exceptId !== null) {
            $addresses->whereKeyNot($exceptId);
        }

        $addresses->update([$field => false]);
    }
}
