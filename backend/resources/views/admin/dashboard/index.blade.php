@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Sales, orders, stock, customers, payments, and promotions at a glance.')

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@push('styles')
    <style>
        .dashboard-hero {
            background:
                linear-gradient(135deg, rgba(15, 118, 110, .96), rgba(37, 99, 235, .92)),
                radial-gradient(circle at 85% 15%, rgba(255, 255, 255, .2), transparent 28%);
            border-radius: .5rem;
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        .dashboard-hero::after {
            background: rgba(255, 255, 255, .14);
            content: "";
            height: 180px;
            position: absolute;
            right: -60px;
            top: -60px;
            transform: rotate(25deg);
            width: 220px;
        }

        .dashboard-hero .hero-content {
            position: relative;
            z-index: 1;
        }

        .action-tile {
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-left: 4px solid var(--bs-primary);
            border-radius: .5rem;
            color: var(--bs-body-color);
            display: flex;
            height: 100%;
            justify-content: space-between;
            min-height: 86px;
            padding: 1rem;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .action-tile:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .metric-value {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .action-count {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: .85rem;
            font-weight: 700;
            justify-content: center;
            min-width: 2rem;
            padding: .2rem .55rem;
        }

        .dashboard-table td,
        .dashboard-table th {
            vertical-align: middle;
        }

        .summary-strip {
            background: var(--bs-tertiary-bg);
            border-radius: .5rem;
            padding: .75rem 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-hero p-4 mb-3">
        <div class="hero-content d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="text-white-50 fw-semibold mb-1">{{ now()->format('l, d M Y') }}</div>
                <h2 class="h3 mb-2">Commerce Command Center</h2>
                <p class="mb-0 text-white-50">Focus on revenue, pending work, stock risk, and customer activity.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('orders.view')
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm">Orders</a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-light btn-sm">Sales Report</a>
                @endcan
                @can('inventory.view')
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-light btn-sm">Inventory</a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-primary stat-card">
                <div class="inner">
                    <h3>{{ number_format((float) $sales['today_sales'], 2) }}</h3>
                    <p>Today revenue</p>
                </div>
                <div class="small-box-icon"><span class="app-icon" aria-hidden="true">BDT</span></div>
                <a href="{{ route('admin.reports.sales') }}" class="small-box-footer link-light link-underline-opacity-0">Open sales <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-success stat-card">
                <div class="inner">
                    <h3>{{ number_format((float) $sales['month_sales'], 2) }}</h3>
                    <p>Month revenue</p>
                </div>
                <div class="small-box-icon"><span class="app-icon" aria-hidden="true">M</span></div>
                <a href="{{ route('admin.reports.sales') }}" class="small-box-footer link-light link-underline-opacity-0">Review trend <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-warning stat-card">
                <div class="inner">
                    <h3>{{ $orders['total_orders'] }}</h3>
                    <p>Total orders</p>
                </div>
                <div class="small-box-icon"><span class="app-icon" aria-hidden="true">O</span></div>
                <a href="{{ route('admin.orders.index') }}" class="small-box-footer link-dark link-underline-opacity-0">Manage orders <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-danger stat-card">
                <div class="inner">
                    <h3>{{ $inventory['low_stock_count'] }}</h3>
                    <p>Low-Stock Summary</p>
                </div>
                <div class="small-box-icon"><span class="app-icon" aria-hidden="true">!</span></div>
                <a href="{{ route('admin.inventory.index') }}" class="small-box-footer link-light link-underline-opacity-0">Fix stock <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title mb-0">Operational Action Queue</h3>
                            <p class="text-secondary mb-0 mt-1">The work most likely to affect revenue or customer experience.</p>
                        </div>
                        <span class="badge text-bg-light">{{ $sales['today_orders'] }} orders today</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($actions as $action)
                            <div class="col-md-6">
                                <a href="{{ route($action['route']) }}" class="text-decoration-none">
                                    <div class="action-tile">
                                        <div>
                                            <div class="text-secondary small">{{ $action['label'] }}</div>
                                            <div class="metric-value">{{ $action['count'] }}</div>
                                        </div>
                                        <span class="action-count text-bg-{{ $action['tone'] }}">{{ $action['count'] }}</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Business Pulse</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Average order value</span>
                        <strong>{{ number_format((float) $sales['average_order_value'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Paid amount</span>
                        <strong>{{ number_format((float) $payments['paid_amount'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Pending payment</span>
                        <strong>{{ number_format((float) $payments['pending_amount'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Coupon discount</span>
                        <strong>{{ number_format((float) $coupons['total_discount'], 2) }}</strong>
                    </div>
                    <hr>
                    @php($availableRatio = $inventory['total_on_hand'] > 0 ? min(100, round(($inventory['total_available'] / $inventory['total_on_hand']) * 100)) : 0)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Available stock</span>
                        <strong>{{ $inventory['total_available'] }}</strong>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Available stock ratio">
                        <div class="progress-bar bg-success" style="width: {{ $availableRatio }}%">{{ $availableRatio }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Recent Orders</h3>
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recent_orders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order['id']) }}" class="fw-semibold text-decoration-none">{{ $order['order_number'] }}</a>
                                            <div class="small text-secondary">{{ $order['placed_at'] ?? 'Not placed' }}</div>
                                        </td>
                                        <td>{{ $order['customer_name'] ?? 'Customer' }}</td>
                                        <td>
                                            <span class="badge text-bg-secondary">{{ str($order['status'])->replace('_', ' ')->title() }}</span>
                                            <span class="badge text-bg-light">{{ str($order['payment_status'])->replace('_', ' ')->title() }}</span>
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $order['grand_total'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">No orders yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100" data-loading-state="order-summary-loading">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Order Summary</h3>
                </div>
                <div class="card-body">
                    <div class="summary-strip d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary">Total orders</span>
                        <strong>{{ $orders['total_orders'] }}</strong>
                    </div>
                    @forelse ($orders['status_counts'] as $status => $count)
                        @php($ratio = $orders['total_orders'] > 0 ? round(($count / $orders['total_orders']) * 100) : 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ str($status)->replace('_', ' ')->title() }}</span>
                                <span class="fw-semibold">{{ $count }}</span>
                            </div>
                            <div class="progress" role="progressbar" aria-label="{{ $status }} orders">
                                <div class="progress-bar" style="width: {{ $ratio }}%">{{ $ratio }}%</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No order status data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-4">
            <div class="card h-100" data-loading-state="customer-summary-loading">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Customer Summary</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7">Total customers</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $customers['total_customers'] }}</dd>
                        <dt class="col-7">Active</dt>
                        <dd class="col-5 text-end">{{ $customers['active_customers'] }}</dd>
                        <dt class="col-7">With orders</dt>
                        <dd class="col-5 text-end">{{ $customers['customers_with_orders'] }}</dd>
                        <dt class="col-7">Repeat</dt>
                        <dd class="col-5 text-end">{{ $customers['repeat_customers'] }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-primary">Open customers</a>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Coupon Performance</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7">Total coupons</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $coupons['total_coupons'] }}</dd>
                        <dt class="col-7">Active coupons</dt>
                        <dd class="col-5 text-end">{{ $coupons['active_coupons'] }}</dd>
                        <dt class="col-7">Redemptions</dt>
                        <dd class="col-5 text-end">{{ $coupons['total_redemptions'] }}</dd>
                        <dt class="col-7">Discount total</dt>
                        <dd class="col-5 text-end">{{ number_format((float) $coupons['total_discount'], 2) }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-sm btn-outline-primary">Manage coupons</a>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Payment & COD</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7">Total payments</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $payments['total_payments'] }}</dd>
                        <dt class="col-7">Failed payments</dt>
                        <dd class="col-5 text-end">{{ $payments['failed_count'] }}</dd>
                        <dt class="col-7">COD pending</dt>
                        <dd class="col-5 text-end">{{ $payments['cod_pending_count'] }}</dd>
                        <dt class="col-7">COD collected</dt>
                        <dd class="col-5 text-end">{{ $payments['cod_collected_count'] }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">Review payments</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <div class="card" data-loading-state="low-stock-summary-loading">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Low-Stock Items</h3>
                        <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-primary">Open inventory</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Available</th>
                                    <th>Threshold</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($low_stock_rows as $stock)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $stock['product_name'] ?? 'Unassigned product' }}</div>
                                            <div class="small text-secondary">{{ $stock['variant_name'] ?? 'Default' }}</div>
                                        </td>
                                        <td>{{ $stock['sku'] }}</td>
                                        <td><span class="badge text-bg-warning">{{ $stock['available_quantity'] }}</span></td>
                                        <td>{{ $stock['low_stock_threshold'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">No low-stock items right now.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card mb-3">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Coupons Expiring Soon</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($expiring_coupons as $coupon)
                            <a href="{{ route('admin.coupons.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="fw-semibold">{{ $coupon['code'] }}</span>
                                    <span class="d-block small text-secondary">{{ $coupon['name'] }}</span>
                                </span>
                                <span class="badge text-bg-warning">{{ $coupon['ends_at'] }}</span>
                            </a>
                        @empty
                            <div class="list-group-item text-secondary">No coupons expiring in the next 7 days.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Recent Users</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped dashboard-table mb-0">
                            <tbody>
                                @forelse ($recent_users as $user)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="small text-secondary">{{ $user->email }}</div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($user->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center py-4 text-secondary">No users found yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
