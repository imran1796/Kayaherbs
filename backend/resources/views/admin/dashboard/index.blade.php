@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Operational summary for orders, customers, and inventory.')

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4 col-12">
            <div class="small-box text-bg-primary stat-card">
                <div class="inner">
                    <h3>{{ $stats['total_users'] }}</h3>
                    <p>Total registered users</p>
                </div>
                <div class="small-box-icon">
                    <span class="app-icon" aria-hidden="true">U</span>
                </div>
                <a href="{{ route('admin.users.index') }}" class="small-box-footer link-light link-underline-opacity-0">
                    Open users <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box text-bg-success stat-card">
                <div class="inner">
                    <h3>{{ $stats['active_users'] }}</h3>
                    <p>Active user accounts</p>
                </div>
                <div class="small-box-icon">
                    <span class="app-icon" aria-hidden="true">A</span>
                </div>
                <a href="{{ route('admin.users.index') }}" class="small-box-footer link-light link-underline-opacity-0">
                    Review accounts <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box text-bg-warning stat-card">
                <div class="inner">
                    <h3>{{ $stats['inactive_users'] }}</h3>
                    <p>Inactive or disabled users</p>
                </div>
                <div class="small-box-icon">
                    <span class="app-icon" aria-hidden="true">I</span>
                </div>
                <a href="{{ route('admin.users.index') }}" class="small-box-footer link-dark link-underline-opacity-0">
                    Check status <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Order Summary</h3>
                </div>
                <div class="card-body">
                    <div class="placeholder-glow d-none" id="order-summary-loading">
                        <span class="placeholder col-8"></span>
                        <span class="placeholder col-6"></span>
                        <span class="placeholder col-10"></span>
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-7">Total orders</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $orderSummary['total_orders'] }}</dd>
                        <dt class="col-7">Pending</dt>
                        <dd class="col-5 text-end">{{ $orderSummary['status_counts']['pending'] ?? 0 }}</dd>
                        <dt class="col-7">Processing</dt>
                        <dd class="col-5 text-end">{{ $orderSummary['status_counts']['processing'] ?? 0 }}</dd>
                        <dt class="col-7">Delivered</dt>
                        <dd class="col-5 text-end">{{ $orderSummary['status_counts']['delivered'] ?? 0 }}</dd>
                        <dt class="col-7">Cancelled</dt>
                        <dd class="col-5 text-end">{{ $orderSummary['status_counts']['cancelled'] ?? 0 }}</dd>
                    </dl>
                    @if ($orderSummary['total_orders'] === 0)
                        <p class="text-secondary mb-0 mt-3">No orders have been placed yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Customer Summary</h3>
                </div>
                <div class="card-body">
                    <div class="placeholder-glow d-none" id="customer-summary-loading">
                        <span class="placeholder col-9"></span>
                        <span class="placeholder col-5"></span>
                        <span class="placeholder col-7"></span>
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-7">Total customers</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $customerSummary['total_customers'] }}</dd>
                        <dt class="col-7">Active</dt>
                        <dd class="col-5 text-end">{{ $customerSummary['active_customers'] }}</dd>
                        <dt class="col-7">With orders</dt>
                        <dd class="col-5 text-end">{{ $customerSummary['customers_with_orders'] }}</dd>
                        <dt class="col-7">Repeat customers</dt>
                        <dd class="col-5 text-end">{{ $customerSummary['repeat_customers'] }}</dd>
                    </dl>
                    @if ($customerSummary['total_customers'] === 0)
                        <p class="text-secondary mb-0 mt-3">No customers found yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Low-Stock Summary</h3>
                </div>
                <div class="card-body">
                    <div class="placeholder-glow d-none" id="low-stock-summary-loading">
                        <span class="placeholder col-10"></span>
                        <span class="placeholder col-6"></span>
                        <span class="placeholder col-8"></span>
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-7">Tracked variants</dt>
                        <dd class="col-5 text-end">{{ $lowStockSummary['tracked_variants'] }}</dd>
                        <dt class="col-7">Available stock</dt>
                        <dd class="col-5 text-end">{{ $lowStockSummary['total_available'] }}</dd>
                        <dt class="col-7">Low-stock items</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $lowStockSummary['low_stock_count'] }}</dd>
                    </dl>
                    @if ($lowStockSummary['low_stock_count'] === 0)
                        <p class="text-secondary mb-0 mt-3">No low-stock items right now.</p>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">Open inventory</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Low-Stock Items</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Available</th>
                                    <th>Threshold</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockRows as $stock)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $stock['product_name'] ?? 'Unassigned product' }}</div>
                                            <div class="small text-secondary">{{ $stock['variant_name'] ?? 'Default' }}</div>
                                        </td>
                                        <td>{{ $stock['sku'] }}</td>
                                        <td>{{ $stock['available_quantity'] }}</td>
                                        <td>{{ $stock['low_stock_threshold'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">
                                            No low-stock items found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Recent users</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="small text-secondary">{{ $user->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-secondary">
                                            No users found yet.
                                        </td>
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
