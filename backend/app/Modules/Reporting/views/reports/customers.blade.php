@extends('admin.layouts.app')

@section('title', 'Customer Report')
@section('page_title', 'Customer Report')
@section('page_subtitle', 'Review customer totals and top customers.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Customer Report</li>
@endsection

@section('content')
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-primary">Sales</a>
        <a href="{{ route('admin.reports.orders') }}" class="btn btn-outline-primary">Orders</a>
        <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-primary">Inventory</a>
        <a href="{{ route('admin.reports.customers') }}" class="btn btn-primary">Customers</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="customer-report-filter-form" class="row g-2">
                <div class="col-md-3">
                    <label for="customers_from" class="form-label">From</label>
                    <input id="customers_from" type="date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="customers_to" class="form-label">To</label>
                    <input id="customers_to" type="date" class="form-control">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-customer-report">Reset</button>
                    <button type="button" class="btn btn-outline-primary" id="refresh-customer-report">Refresh</button>
                    <button type="button" class="btn btn-outline-success" id="export-customer-report">Export CSV</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Total customers</div><div class="h4 mb-0" id="total-customers-value">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Active</div><div class="h4 mb-0" id="active-customers-value">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">With orders</div><div class="h4 mb-0" id="customers-with-orders-value">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Repeat</div><div class="h4 mb-0" id="repeat-customers-value">0</div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Customers</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Orders</th>
                            <th>Total spent</th>
                        </tr>
                    </thead>
                    <tbody id="customer-report-table-body">
                        <tr><td colspan="4" class="text-center py-4 text-secondary">Loading customer report...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const customerReportRoute = @json(route('admin.reports.customers.data'));
        const customerExportRoute = @json(route('admin.reports.export', ['report' => 'customers']));

        function escapeHtml(value) {
            return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
        }

        function customerFilters() {
            return {
                from: $('#customers_from').val(),
                to: $('#customers_to').val(),
            };
        }

        function renderCustomerReport(report) {
            const summary = report.summary || {};
            $('#total-customers-value').text(summary.total_customers || 0);
            $('#active-customers-value').text(summary.active_customers || 0);
            $('#customers-with-orders-value').text(summary.customers_with_orders || 0);
            $('#repeat-customers-value').text(summary.repeat_customers || 0);

            if (!report.top_customers?.length) {
                $('#customer-report-table-body').html('<tr><td colspan="4" class="text-center py-4 text-secondary">No customer rows found.</td></tr>');
                return;
            }

            $('#customer-report-table-body').html(report.top_customers.map((customer) => `
                <tr>
                    <td>${escapeHtml(customer.name)}</td>
                    <td>${escapeHtml(customer.email)}</td>
                    <td>${escapeHtml(customer.orders_count)}</td>
                    <td>${escapeHtml(customer.total_spent)}</td>
                </tr>
            `).join(''));
        }

        function loadCustomerReport() {
            return $.ajax({
                url: customerReportRoute,
                method: 'GET',
                dataType: 'json',
                data: customerFilters(),
                headers: { 'Accept': 'application/json' },
            }).then((body) => {
                renderCustomerReport(body.data);
            }).catch(() => {
                adminToast('Unable to load customer report.', 'danger');
            });
        }

        function exportCustomerReport() {
            const params = new URLSearchParams(customerFilters());
            window.location.href = `${customerExportRoute}?${params.toString()}`;
        }

        $('#customer-report-filter-form').on('submit', function (event) {
            event.preventDefault();
            loadCustomerReport();
        });
        $('#refresh-customer-report').on('click', loadCustomerReport);
        $('#export-customer-report').on('click', exportCustomerReport);
        $('#reset-customer-report').on('click', function () {
            $('#customer-report-filter-form')[0].reset();
            loadCustomerReport();
        });
        loadCustomerReport();
    </script>
@endpush
