<?php

namespace App\Modules\Customer\Services;

use App\Models\CustomerAddress;
use App\Models\User;
use App\Modules\Customer\Repositories\CustomerAddressRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerAddressService
{
    public function __construct(
        private readonly CustomerAddressRepository $addresses
    ) {}

    public function create(User $customer, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $data): CustomerAddress {
            $this->clearDefaults($customer, $data);

            return $this->addresses->create($customer, $data);
        });
    }

    public function createForCustomer(int $customerId, array $data): CustomerAddress
    {
        return $this->create($this->addresses->findCustomerOrFail($customerId), $data);
    }

    public function list(User $customer): Collection
    {
        return $this->addresses->listForCustomer($customer);
    }

    public function findOrFail(User $customer, int $id): CustomerAddress
    {
        return $this->addresses->findForCustomerOrFail($customer, $id);
    }

    public function findForCustomerOrFail(int $customerId, int $id): CustomerAddress
    {
        return $this->findOrFail($this->addresses->findCustomerOrFail($customerId), $id);
    }

    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($address, $data): CustomerAddress {
            $this->clearDefaults($address->customer, $data, $address->id);

            return $this->addresses->update($address, $data);
        });
    }

    public function delete(CustomerAddress $address): void
    {
        $this->addresses->delete($address);
    }

    private function clearDefaults(User $customer, array $data, ?int $exceptId = null): void
    {
        foreach (['is_default_shipping', 'is_default_billing'] as $field) {
            if (! ($data[$field] ?? false)) {
                continue;
            }

            $this->addresses->clearDefault($customer, $field, $exceptId);
        }
    }
}
