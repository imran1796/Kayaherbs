@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page_title', 'Customers')
@section('page_subtitle', '')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Customers</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title">Customer List</h3>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-customers">Refresh</button>
            </div>
        </div>
        <div class="card-body border-top">
            <form id="customer-filter-form" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label for="customer_search" class="form-label">Search</label>
                    <input id="customer_search" class="form-control form-control-sm" placeholder="Name, email, or phone">
                </div>
                <div class="col-md-4">
                    <label for="customer_status_filter" class="form-label">Status</label>
                    <select id="customer_status_filter" class="form-select form-select-sm js-select2" data-placeholder="All statuses">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="clear-customer-filters">Clear</button>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customer-table-body">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">Loading customers...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const customerRoutes = {
            data: @json(route('admin.customers.data')),
            show: @json(route('admin.customers.show', ['id' => '__ID__'])),
        };

        function routeFor(template, id) {
            return template.replace('__ID__', id);
        }

        function initCustomerSelect2() {
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

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function statusBadge(status) {
            const badge = status === 'active' ? 'success' : (status === 'suspended' ? 'danger' : 'secondary');

            return `<span class="badge text-bg-${badge}">${escapeHtml(status)}</span>`;
        }

        function renderCustomers(customers) {
            if (!customers.length) {
                $('#customer-table-body').html('<tr><td colspan="7" class="text-center py-4 text-secondary">No customers match these filters.</td></tr>');
                return;
            }

            $('#customer-table-body').html(customers.map((customer) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(customer.name)}</td>
                    <td>${escapeHtml(customer.email)}</td>
                    <td>${escapeHtml(customer.phone || '')}</td>
                    <td>${statusBadge(customer.status)}</td>
                    <td>${customer.orders_count ?? 0}</td>
                    <td>${escapeHtml(customer.created_at || '')}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="${routeFor(customerRoutes.show, customer.id)}">View</a>
                    </td>
                </tr>
            `).join(''));
        }

        function loadCustomers() {
            const params = new URLSearchParams();
            const search = $('#customer_search').val().trim();
            const status = $('#customer_status_filter').val();

            if (search) {
                params.set('search', search);
            }

            if (status) {
                params.set('status', status);
            }

            const url = params.toString() ? `${customerRoutes.data}?${params.toString()}` : customerRoutes.data;

            return $.ajax({
                url,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                renderCustomers(body.data);
            }).catch(() => {
                adminToast('Unable to load customers.', 'danger');
            });
        }

        $('#refresh-customers').on('click', loadCustomers);
        $('#customer-filter-form').on('submit', function (event) {
            event.preventDefault();
            loadCustomers();
        });
        $('#clear-customer-filters').on('click', function () {
            $('#customer_search').val('');
            $('#customer_status_filter').val('').trigger('change.select2');
            loadCustomers();
        });
        initCustomerSelect2();
        loadCustomers();
    </script>
@endpush
