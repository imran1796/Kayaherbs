<?php

namespace App\Modules\Checkout\Services;

use App\Core\Services\AuditLogger;
use App\Events\OrderConfirmationGenerated;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Modules\Cart\Services\CartService;
use App\Modules\Checkout\Repositories\CheckoutRepository;
use App\Modules\Customer\Services\CustomerAddressService;
use App\Modules\Inventory\Services\InventoryStockService;
use App\Modules\Order\Services\OrderLifecycleService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Promotion\Services\CouponDiscountService;
use App\Modules\Promotion\Services\CouponRedemptionService;
use App\Modules\Shipping\Services\DeliveryRateResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutRepository $checkout,
        private readonly CustomerAddressService $addresses,
        private readonly InventoryStockService $inventory,
        private readonly AuditLogger $auditLogger,
        private readonly OrderLifecycleService $orderLifecycle,
        private readonly PaymentService $payments,
        private readonly CouponDiscountService $couponDiscounts,
        private readonly CouponRedemptionService $couponRedemptions,
        private readonly DeliveryRateResolverService $deliveryRates
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateCheckout(User $customer, array $data): array
    {
        return DB::transaction(function () use ($customer, $data): array {
            return $this->checkoutPayload($customer, $data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitCheckout(User $customer, array $data): Order
    {
        $existingOrder = $this->checkout->findOrderByIdempotencyKey($customer, $data['idempotency_key']);

        if ($existingOrder !== null) {
            $existingOrder->wasRecentlyCreated = false;

            return $existingOrder;
        }

        $order = DB::transaction(function () use ($customer, $data): Order {
            $payload = $this->checkoutPayload($customer, $data);
            /** @var Cart $cart */
            $cart = $payload['cart'];

            $order = $this->checkout->createOrder([
                'user_id' => $customer->id,
                'cart_id' => $cart->id,
                'coupon_id' => $cart->coupon_id,
                'coupon_code' => $cart->coupon_code,
                'idempotency_key' => $data['idempotency_key'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'shipping_method_code' => $payload['shipping_method']['code'],
                'shipping_method_name' => $payload['shipping_method']['name'],
                'payment_method_code' => $payload['payment_method']['code'],
                'payment_method_name' => $payload['payment_method']['name'],
                'subtotal' => $payload['totals']['subtotal'],
                'shipping_total' => $payload['totals']['shipping_total'],
                'discount_total' => $payload['totals']['discount_total'],
                'grand_total' => $payload['totals']['grand_total'],
                'currency' => $payload['totals']['currency'],
                'shipping_address' => $this->addressSnapshot($payload['shipping_address']),
                'billing_address' => $this->addressSnapshot($payload['billing_address']),
                'placed_at' => now(),
            ]);
            $this->couponRedemptions->recordForOrder($order, $customer);

            foreach ($this->checkout->availableCartItems($cart) as $item) {
                $this->checkout->createOrderItem($order, $this->orderItemSnapshot($item));
                $this->inventory->reserve(
                    $item->product_variant_id,
                    $item->quantity,
                    $customer,
                    'Reserved during checkout.',
                    ['order_number' => $order->order_number]
                );
            }

            $this->orderLifecycle->recordCreation($order, $customer, 'Order placed during checkout.', [
                'cart_id' => $cart->id,
                'idempotency_key' => $data['idempotency_key'],
            ]);
            $this->payments->createInitialPaymentForOrder($order, $customer);

            $this->checkout->completeCart($cart);

            return $this->checkout->loadOrder($order);
        });

        $this->generateOrderConfirmation($order, $customer);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitGuestCheckout(array $data): Order
    {
        $customer = $this->findOrCreateGuestCustomer($data['customer']);
        $existingOrder = $this->checkout->findOrderByIdempotencyKey($customer, $data['idempotency_key']);

        if ($existingOrder !== null) {
            $existingOrder->wasRecentlyCreated = false;

            return $existingOrder;
        }

        $order = DB::transaction(function () use ($customer, $data): Order {
            $cart = $this->carts->getGuestCart((string) $data['cart_token']);
            $this->ensureCartCanCheckout($cart);

            $shippingAddress = $this->addresses->create($customer, [
                ...$data['shipping_address'],
                'is_default_shipping' => false,
                'is_default_billing' => false,
            ]);
            $billingAddress = $shippingAddress;
            $subtotal = $this->cartSubtotal($cart);
            $shippingMethod = $this->resolveShippingMethod($shippingAddress, $data, $subtotal);
            $paymentMethod = $this->resolvePaymentMethod($data);
            $shippingTotal = (float) $shippingMethod['amount'];
            $discountTotals = $this->couponDiscounts->checkoutTotals($cart, $shippingTotal);
            $discountTotal = (float) $discountTotals['discount_total'];
            $coupon = $discountTotals['coupon'];

            if ($coupon !== null && ! $coupon['eligible']) {
                throw ValidationException::withMessages([
                    'coupon' => $coupon['reasons'],
                ]);
            }

            $order = $this->checkout->createOrder([
                'user_id' => $customer->id,
                'cart_id' => $cart->id,
                'coupon_id' => $cart->coupon_id,
                'coupon_code' => $cart->coupon_code,
                'idempotency_key' => $data['idempotency_key'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'shipping_method_code' => $shippingMethod['code'],
                'shipping_method_name' => $shippingMethod['name'],
                'payment_method_code' => $paymentMethod['code'],
                'payment_method_name' => $paymentMethod['name'],
                'subtotal' => $this->money($subtotal),
                'shipping_total' => $this->money($shippingTotal),
                'discount_total' => $this->money($discountTotal),
                'grand_total' => $this->money(max(0, $subtotal + $shippingTotal - $discountTotal)),
                'currency' => (string) config('store.defaults.currency', 'BDT'),
                'shipping_address' => $this->addressSnapshot($shippingAddress),
                'billing_address' => $this->addressSnapshot($billingAddress),
                'placed_at' => now(),
            ]);
            $this->couponRedemptions->recordForOrder($order, $customer);

            foreach ($this->checkout->availableCartItems($cart) as $item) {
                $this->checkout->createOrderItem($order, $this->orderItemSnapshot($item));
                $this->inventory->reserve(
                    $item->product_variant_id,
                    $item->quantity,
                    $customer,
                    'Reserved during guest checkout.',
                    ['order_number' => $order->order_number]
                );
            }

            $this->orderLifecycle->recordCreation($order, $customer, 'Order placed during guest checkout.', [
                'cart_id' => $cart->id,
                'idempotency_key' => $data['idempotency_key'],
            ]);
            $this->payments->createInitialPaymentForOrder($order, $customer);
            $this->checkout->completeCart($cart);

            return $this->checkout->loadOrder($order);
        });

        $this->generateOrderConfirmation($order, $customer);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function checkoutPayload(User $customer, array $data): array
    {
        $cart = $this->carts->getCustomerCart($customer);

        $this->ensureCartCanCheckout($cart);

        $shippingAddress = $this->resolveAddress($customer, $data, 'shipping');
        $billingAddress = ($data['billing_same_as_shipping'] ?? true)
            ? $shippingAddress
            : $this->resolveAddress($customer, $data, 'billing');
        $subtotal = $this->cartSubtotal($cart);
        $shippingMethod = $this->resolveShippingMethod($shippingAddress, $data, $subtotal);
        $paymentMethod = $this->resolvePaymentMethod($data);
        $shippingTotal = (float) $shippingMethod['amount'];
        $discountTotals = $this->couponDiscounts->checkoutTotals($cart, $shippingTotal);
        $discountTotal = (float) $discountTotals['discount_total'];
        $coupon = $discountTotals['coupon'];

        if ($coupon !== null && ! $coupon['eligible']) {
            throw ValidationException::withMessages([
                'coupon' => $coupon['reasons'],
            ]);
        }

        return [
            'checkout_ready' => true,
            'cart' => $cart,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'shipping_method' => $shippingMethod,
            'payment_method' => $paymentMethod,
            'totals' => [
                'subtotal' => $this->money($subtotal),
                'shipping_total' => $this->money($shippingTotal),
                'discount_total' => $this->money($discountTotal),
                'grand_total' => $this->money(max(0, $subtotal + $shippingTotal - $discountTotal)),
                'currency' => (string) config('store.defaults.currency', 'BDT'),
            ],
            'steps' => [
                'cart' => 'passed',
                'shipping_address' => 'passed',
                'billing_address' => 'passed',
                'shipping_method' => 'passed',
                'payment_method' => 'passed',
            ],
        ];
    }

    private function ensureCartCanCheckout(Cart $cart): void
    {
        if (! $this->checkout->hasAvailableItems($cart)) {
            throw ValidationException::withMessages([
                'cart' => ['Cart must contain at least one available item.'],
            ]);
        }

        if ($this->checkout->hasUnavailableItems($cart)) {
            throw ValidationException::withMessages([
                'cart' => ['Remove unavailable items before checkout.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAddress(User $customer, array $data, string $type): CustomerAddress
    {
        $addressId = $data[$type.'_address_id'] ?? null;

        if ($addressId !== null) {
            return $this->checkout->findCustomerAddress($customer, (int) $addressId);
        }

        $addressData = $data[$type.'_address'] ?? null;

        if (! is_array($addressData)) {
            throw ValidationException::withMessages([
                $type.'_address' => ['Select an existing address or provide a new '.$type.' address.'],
            ]);
        }

        return $this->addresses->create($customer, [
            ...$addressData,
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveShippingMethod(CustomerAddress $shippingAddress, array $data, float $subtotal): array
    {
        $requestedCode = isset($data['shipping_method']) ? (string) $data['shipping_method'] : null;
        $databaseRate = $this->deliveryRates->resolve($shippingAddress, $subtotal, $requestedCode);

        if ($databaseRate !== null) {
            return $databaseRate;
        }

        if ($this->deliveryRates->hasConfiguredRates()) {
            throw ValidationException::withMessages([
                'shipping_method' => [$requestedCode === null
                    ? 'No delivery rate is available for this address.'
                    : 'Selected shipping method is not available for this address.'],
            ]);
        }

        $code = (string) ($requestedCode ?? config('checkout.shipping.default_method', 'standard'));
        $method = config('checkout.shipping.methods.'.$code);

        if (! is_array($method) || ! ($method['active'] ?? false)) {
            throw ValidationException::withMessages([
                'shipping_method' => ['Selected shipping method is not available.'],
            ]);
        }

        $countries = $method['countries'] ?? [];

        if ($countries !== [] && ! in_array($shippingAddress->country, $countries, true)) {
            throw ValidationException::withMessages([
                'shipping_method' => ['Selected shipping method is not available for this address.'],
            ]);
        }

        return [
            'code' => $code,
            'name' => $method['name'],
            'amount' => $this->money((float) $method['amount']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolvePaymentMethod(array $data): array
    {
        $code = (string) ($data['payment_method'] ?? config('checkout.payment.default_method', 'cod'));
        $method = config('checkout.payment.methods.'.$code);

        if (! is_array($method) || ! ($method['active'] ?? false)) {
            throw ValidationException::withMessages([
                'payment_method' => ['Selected payment method is not available.'],
            ]);
        }

        return [
            'code' => $code,
            'name' => $method['name'],
        ];
    }

    private function cartSubtotal(Cart $cart): float
    {
        return $this->checkout->availableCartSubtotal($cart);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    private function addressSnapshot(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderItemSnapshot(CartItem $item): array
    {
        return [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'product_name' => $item->product_name,
            'variant_name' => $item->variant_name,
            'sku' => $item->sku,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'line_total' => $item->line_total,
            'snapshot' => [
                'cart_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ],
        ];
    }

    private function generateOrderConfirmation(Order $order, User $customer): void
    {
        OrderConfirmationGenerated::dispatch($order);

        $this->auditLogger->record(
            'order.confirmation.generated',
            actor: $customer,
            auditable: $order,
            metadata: [
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total,
                'currency' => $order->currency,
            ],
            guard: 'sanctum'
        );
    }

    /**
     * @param  array<string, mixed>  $customerData
     */
    private function findOrCreateGuestCustomer(array $customerData): User
    {
        $phone = trim((string) $customerData['phone']);
        $email = trim((string) ($customerData['email'] ?? ''));
        $lookupEmail = $email !== '' ? $email : $this->guestEmailFromPhone($phone);

        /** @var User|null $customer */
        $customer = User::query()
            ->customers()
            ->where(function ($query) use ($lookupEmail, $phone): void {
                $query
                    ->where('email', $lookupEmail)
                    ->orWhere('phone', $phone);
            })
            ->first();

        if ($customer !== null) {
            $customer->fill([
                'name' => $customerData['name'],
                'phone' => $phone,
            ]);

            if ($email !== '' && str_ends_with((string) $customer->email, '@guest.kayaherbs.local')) {
                $customer->email = $email;
            }

            $customer->save();
            $this->ensureCustomerRole($customer, 'guest_checkout_existing_customer');

            return $customer;
        }

        $customer = User::query()->create([
            'name' => $customerData['name'],
            'email' => $lookupEmail,
            'phone' => $phone,
            'password' => Hash::make(Str::random(32)),
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->ensureCustomerRole($customer, 'guest_checkout');

        return $customer;
    }

    private function guestEmailFromPhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9a-z]+/i', '', $phone) ?: Str::random(10);

        return strtolower($normalized).'@guest.kayaherbs.local';
    }

    private function ensureCustomerRole(User $customer, string $source): void
    {
        if ($customer->hasRole('customer')) {
            return;
        }

        $customerRole = Role::findOrCreate('customer', (string) config('rbac.guard', 'web'));
        $customer->assignRole($customerRole);
        $this->auditLogger->record(
            'rbac.role.assigned',
            actor: $customer,
            auditable: $customer,
            metadata: ['role' => 'customer', 'source' => $source],
            guard: 'web'
        );
    }
}
