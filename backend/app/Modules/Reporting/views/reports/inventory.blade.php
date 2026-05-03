@extends('admin.layouts.app')

@section('title', 'Inventory Report')
@section('page_title', 'Inventory Report')
@section('page_subtitle', 'Review stock totals and low-stock items.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Inventory Report</li>
@endsection

@section('content')
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-primary">Sales</a>
        <a href="{{ route('admin.reports.orders') }}" class="btn btn-outline-primary">Orders</a>
        <a href="{{ route('admin.reports.inventory') }}" class="btn btn-primary">Inventory</a>
        <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-primary">Customers</a>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-primary" id="refresh-inventory-report">Refresh</button>
        <button type="button" class="btn btn-outline-success ms-2" id="export-inventory-report">Export CSV</button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Tracked variants</div><div class="h4 mb-0" id="tracked-variants-value">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">On hand</div><div class="h4 mb-0" id="on-hand-value">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Available</div><div class="h4 mb-0" id="available-value">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Low stock</div><div class="h4 mb-0" id="low-stock-value">0</div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Low Stock Items</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th>On hand</th>
                            <th>Reserved</th>
                            <th>Available</th>
                            <th>Threshold</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-report-table-body">
                        <tr><td colspan="7" class="text-center py-4 text-secondary">Loading inventory report...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const inventoryReportRoute = @json(route('admin.reports.inventory.data'));
        const inventoryExportRoute = @json(route('admin.reports.export', ['report' => 'inventory']));

        function escapeHtml(value) {
            return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
        }

        function renderInventoryReport(report) {
            const summary = report.summary || {};
            $('#tracked-variants-value').text(summary.tracked_variants || 0);
            $('#on-hand-value').text(summary.total_on_hand || 0);
            $('#available-value').text(summary.total_available || 0);
            $('#low-stock-value').text(summary.low_stock_count || 0);

            if (!report.low_stock?.length) {
                $('#inventory-report-table-body').html('<tr><td colspan="7" class="text-center py-4 text-secondary">No low-stock items.</td></tr>');
                return;
            }

            $('#inventory-report-table-body').html(report.low_stock.map((stock) => `
                <tr>
                    <td>${escapeHtml(stock.product_name || '')}</td>
                    <td>${escapeHtml(stock.variant_name || '')}</td>
                    <td>${escapeHtml(stock.sku || '')}</td>
                    <td>${escapeHtml(stock.quantity_on_hand)}</td>
                    <td>${escapeHtml(stock.quantity_reserved)}</td>
                    <td>${escapeHtml(stock.available_quantity)}</td>
                    <td>${escapeHtml(stock.low_stock_threshold)}</td>
                </tr>
            `).join(''));
        }

        function loadInventoryReport() {
            return $.ajax({
                url: inventoryReportRoute,
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
            }).then((body) => {
                renderInventoryReport(body.data);
            }).catch(() => {
                adminToast('Unable to load inventory report.', 'danger');
            });
        }

        $('#refresh-inventory-report').on('click', loadInventoryReport);
        $('#export-inventory-report').on('click', function () {
            window.location.href = inventoryExportRoute;
        });
        loadInventoryReport();
    </script>
@endpush
