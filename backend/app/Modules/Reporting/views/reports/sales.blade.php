@extends('admin.layouts.app')

@section('title', 'Sales Report')
@section('page_title', 'Sales Report')
@section('page_subtitle', 'Review gross and paid sales by day.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Sales Report</li>
@endsection

@section('content')
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-primary">Sales</a>
        <a href="{{ route('admin.reports.orders') }}" class="btn btn-outline-primary">Orders</a>
        <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-primary">Inventory</a>
        <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-primary">Customers</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="sales-report-filter-form" class="row g-2">
                <div class="col-md-3">
                    <label for="sales_from" class="form-label">From</label>
                    <input id="sales_from" type="date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="sales_to" class="form-label">To</label>
                    <input id="sales_to" type="date" class="form-control">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-sales-report">Reset</button>
                    <button type="button" class="btn btn-outline-primary" id="refresh-sales-report">Refresh</button>
                    <button type="button" class="btn btn-outline-success" id="export-sales-report">Export CSV</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary">Gross sales</div>
                    <div class="h4 mb-0" id="gross-sales-value">0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary">Paid sales</div>
                    <div class="h4 mb-0" id="paid-sales-value">0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary">Daily rows</div>
                    <div class="h4 mb-0" id="sales-row-count">0</div>
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
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Gross sales</th>
                            <th>Paid sales</th>
                        </tr>
                    </thead>
                    <tbody id="sales-report-table-body">
                        <tr>
                            <td colspan="4" class="text-center py-4 text-secondary">Loading sales report...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const salesReportRoute = @json(route('admin.reports.sales.data'));
        const salesExportRoute = @json(route('admin.reports.export', ['report' => 'sales']));

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function salesFilters() {
            return {
                from: $('#sales_from').val(),
                to: $('#sales_to').val(),
            };
        }

        function renderSalesReport(report) {
            $('#gross-sales-value').text(report.gross_sales || '0.00');
            $('#paid-sales-value').text(report.paid_sales || '0.00');
            $('#sales-row-count').text((report.daily || []).length);

            if (!report.daily?.length) {
                $('#sales-report-table-body').html('<tr><td colspan="4" class="text-center py-4 text-secondary">No sales rows found.</td></tr>');
                return;
            }

            $('#sales-report-table-body').html(report.daily.map((row) => `
                <tr>
                    <td>${escapeHtml(row.date)}</td>
                    <td>${escapeHtml(row.orders_count)}</td>
                    <td>${escapeHtml(row.gross_sales)}</td>
                    <td>${escapeHtml(row.paid_sales)}</td>
                </tr>
            `).join(''));
        }

        function loadSalesReport() {
            return $.ajax({
                url: salesReportRoute,
                method: 'GET',
                dataType: 'json',
                data: salesFilters(),
                headers: { 'Accept': 'application/json' },
            }).then((body) => {
                renderSalesReport(body.data);
            }).catch(() => {
                adminToast('Unable to load sales report.', 'danger');
            });
        }

        function exportSalesReport() {
            const params = new URLSearchParams(salesFilters());
            window.location.href = `${salesExportRoute}?${params.toString()}`;
        }

        $('#sales-report-filter-form').on('submit', function (event) {
            event.preventDefault();
            loadSalesReport();
        });
        $('#refresh-sales-report').on('click', loadSalesReport);
        $('#export-sales-report').on('click', exportSalesReport);
        $('#reset-sales-report').on('click', function () {
            $('#sales-report-filter-form')[0].reset();
            loadSalesReport();
        });
        loadSalesReport();
    </script>
@endpush
