@extends('admin.layouts.app')

@section('title', 'Orders Report')
@section('page_title', 'Orders Report')
@section('page_subtitle', 'Review order totals and status counts.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Orders Report</li>
@endsection

@section('content')
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-primary">Sales</a>
        <a href="{{ route('admin.reports.orders') }}" class="btn btn-primary">Orders</a>
        <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-primary">Inventory</a>
        <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-primary">Customers</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="orders-report-filter-form" class="row g-2">
                <div class="col-md-3">
                    <label for="orders_from" class="form-label">From</label>
                    <input id="orders_from" type="date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="orders_to" class="form-label">To</label>
                    <input id="orders_to" type="date" class="form-control">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-orders-report">Reset</button>
                    <button type="button" class="btn btn-outline-primary" id="refresh-orders-report">Refresh</button>
                    <button type="button" class="btn btn-outline-success" id="export-orders-report">Export CSV</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary">Total orders</div>
                    <div class="h4 mb-0" id="total-orders-value">0</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Orders</th>
                        </tr>
                    </thead>
                    <tbody id="orders-report-table-body">
                        <tr>
                            <td colspan="2" class="text-center py-4 text-secondary">Loading orders report...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const ordersReportRoute = @json(route('admin.reports.orders.data'));
        const ordersExportRoute = @json(route('admin.reports.export', ['report' => 'orders']));

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function labelStatus(status) {
            return String(status || '').replaceAll('_', ' ');
        }

        function ordersFilters() {
            return {
                from: $('#orders_from').val(),
                to: $('#orders_to').val(),
            };
        }

        function renderOrdersReport(report) {
            const counts = report.status_counts || {};
            const rows = Object.entries(counts);
            $('#total-orders-value').text(report.total_orders || 0);

            if (!rows.length) {
                $('#orders-report-table-body').html('<tr><td colspan="2" class="text-center py-4 text-secondary">No order rows found.</td></tr>');
                return;
            }

            $('#orders-report-table-body').html(rows.map(([status, count]) => `
                <tr>
                    <td>${escapeHtml(labelStatus(status))}</td>
                    <td>${escapeHtml(count)}</td>
                </tr>
            `).join(''));
        }

        function loadOrdersReport() {
            return $.ajax({
                url: ordersReportRoute,
                method: 'GET',
                dataType: 'json',
                data: ordersFilters(),
                headers: { 'Accept': 'application/json' },
            }).then((body) => {
                renderOrdersReport(body.data);
            }).catch(() => {
                adminToast('Unable to load orders report.', 'danger');
            });
        }

        function exportOrdersReport() {
            const params = new URLSearchParams(ordersFilters());
            window.location.href = `${ordersExportRoute}?${params.toString()}`;
        }

        $('#orders-report-filter-form').on('submit', function (event) {
            event.preventDefault();
            loadOrdersReport();
        });
        $('#refresh-orders-report').on('click', loadOrdersReport);
        $('#export-orders-report').on('click', exportOrdersReport);
        $('#reset-orders-report').on('click', function () {
            $('#orders-report-filter-form')[0].reset();
            loadOrdersReport();
        });
        loadOrdersReport();
    </script>
@endpush
