@extends('admin.layouts.app')

@section('title', 'Orders')
@section('page_title', 'Orders')
@section('page_subtitle', '')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Orders</li>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title">Order List</h3>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-orders">Refresh</button>
            </div>
        </div>
        <div class="card-body border-top">
            <form id="order-filter-form" class="row g-2">
                <div class="col-md-4">
                    <label for="order_search" class="form-label">Search</label>
                    <input id="order_search" class="form-control form-control-sm" placeholder="Order number, customer, email">
                </div>
                <div class="col-md-2">
                    <label for="order_status" class="form-label">Status</label>
                    <select id="order_status" class="form-select form-select-sm js-select2" data-placeholder="Any">
                        <option value="">Any</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="packed">Packed</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="return_requested">Return requested</option>
                        <option value="returned">Returned</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="payment_status" class="form-label">Payment</label>
                    <select id="payment_status" class="form-select form-select-sm js-select2" data-placeholder="Any">
                        <option value="">Any</option>
                        <option value="pending">Pending</option>
                        <option value="authorized">Authorized</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fulfillment_status" class="form-label">Fulfillment</label>
                    <select id="fulfillment_status" class="form-select form-select-sm js-select2" data-placeholder="Any">
                        <option value="">Any</option>
                        <option value="unfulfilled">Unfulfilled</option>
                        <option value="processing">Processing</option>
                        <option value="packed">Packed</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-order-filters">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Fulfillment</th>
                            <th>Total</th>
                            <th>Placed</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="order-table-body">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">Loading orders...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-secondary" id="order-pagination-summary">No orders loaded.</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="load-more-orders">Load more</button>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const orderRoutes = {
            data: @json(route('admin.orders.data')),
            show: @json(route('admin.orders.show', ['id' => '__ID__'])),
        };
        let currentPage = 1;
        let lastPage = 1;
        let loadedOrders = [];

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function badgeClass(status) {
            if (['paid', 'delivered', 'confirmed'].includes(status)) {
                return 'success';
            }

            if (['cancelled', 'failed', 'refunded'].includes(status)) {
                return 'danger';
            }

            if (['shipped', 'packed', 'processing', 'authorized'].includes(status)) {
                return 'primary';
            }

            return 'secondary';
        }

        function statusBadge(status) {
            return `<span class="badge text-bg-${badgeClass(status)}">${escapeHtml(status || '')}</span>`;
        }

        function routeFor(template, id) {
            return template.replace('__ID__', id);
        }

        function initOrderSelect2() {
            if (typeof $.fn.select2 !== 'function') {
                return;
            }

            $('.js-select2').select2({
                width: '100%',
                allowClear: true,
                placeholder: function () {
                    return $(this).data('placeholder') || '';
                },
            });
        }

        function filterParams(page) {
            return {
                page,
                per_page: 15,
                search: $('#order_search').val(),
                status: $('#order_status').val(),
                payment_status: $('#payment_status').val(),
                fulfillment_status: $('#fulfillment_status').val(),
            };
        }

        function renderOrders(orders) {
            if (!orders.length) {
                $('#order-table-body').html('<tr><td colspan="8" class="text-center py-4 text-secondary">No orders found.</td></tr>');
                return;
            }

            $('#order-table-body').html(orders.map((order) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(order.order_number)}</td>
                    <td>
                        <span class="d-block">${escapeHtml(order.customer?.name || 'Guest')}</span>
                        <span class="text-secondary">${escapeHtml(order.customer?.email || '')}</span>
                    </td>
                    <td>${statusBadge(order.status)}</td>
                    <td>${statusBadge(order.payment_status)}</td>
                    <td>${statusBadge(order.fulfillment_status)}</td>
                    <td>${escapeHtml(order.totals?.grand_total || '')} ${escapeHtml(order.totals?.currency || '')}</td>
                    <td>${escapeHtml(order.lifecycle?.placed_at || order.created_at || '')}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="${routeFor(orderRoutes.show, order.id)}">View</a>
                    </td>
                </tr>
            `).join(''));
        }

        function updatePagination(meta) {
            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            $('#order-pagination-summary').text(`Showing ${loadedOrders.length} of ${meta.total || loadedOrders.length} orders`);
            $('#load-more-orders').prop('disabled', currentPage >= lastPage);
        }

        function loadOrders(page = 1, append = false) {
            return $.ajax({
                url: orderRoutes.data,
                method: 'GET',
                dataType: 'json',
                data: filterParams(page),
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                loadedOrders = append ? loadedOrders.concat(body.data) : body.data;
                renderOrders(loadedOrders);
                updatePagination(body.meta?.pagination || {});
            }).catch(() => {
                adminToast('Unable to load orders.', 'danger');
            });
        }

        $('#refresh-orders').on('click', function () {
            loadOrders(1);
        });
        $('#order-filter-form').on('submit', function (event) {
            event.preventDefault();
            loadOrders(1);
        });
        $('#reset-order-filters').on('click', function () {
            $('#order-filter-form')[0].reset();
            $('#order_status, #payment_status, #fulfillment_status').trigger('change.select2');
            loadOrders(1);
        });
        $('#load-more-orders').on('click', function () {
            if (currentPage < lastPage) {
                loadOrders(currentPage + 1, true);
            }
        });
        initOrderSelect2();
        loadOrders();
    </script>
@endpush
