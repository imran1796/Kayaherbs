@extends('admin.layouts.app')

@section('title', 'Inventory')
@section('page_title', 'Inventory')
@section('page_subtitle', '')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Inventory</li>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title">Stock List</h3>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.inventory.history') }}" class="btn btn-sm btn-outline-secondary">History</a>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-stocks">Refresh</button>
                        </div>
                    </div>
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
                                    <th>Status</th>
                                    @can('inventory.adjust')
                                        <th class="text-end">Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody id="stock-table-body">
                                <tr>
                                    <td colspan="@can('inventory.adjust') 8 @else 7 @endcan" class="text-center py-4 text-secondary">Loading stock...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h3 class="card-title">Low Stock</h3>
                            <p class="text-secondary mb-0 mt-1">Items at or below threshold.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-low-stock">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Available</th>
                                    <th>Threshold</th>
                                </tr>
                            </thead>
                            <tbody id="low-stock-table-body">
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-secondary">Loading low-stock items...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @can('inventory.adjust')
                <form id="stock-adjustment-form" class="card">
                    <div class="card-header">
                        <h3 class="card-title">Adjust Stock</h3>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="adjust_variant_id">

                        <div class="mb-3">
                            <label for="adjust_variant_label" class="form-label">Selected variant</label>
                            <input id="adjust_variant_label" class="form-control form-control-sm" value="Select a stock row" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="quantity_delta" class="form-label">Quantity change</label>
                            <input id="quantity_delta" type="number" step="1" class="form-control form-control-sm" placeholder="Use negative number to reduce" required>
                        </div>

                        <div>
                            <label for="adjustment_note" class="form-label">Note</label>
                            <textarea id="adjustment_note" rows="3" class="form-control form-control-sm" placeholder="Reason for this adjustment"></textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="reset-adjustment-form">Reset</button>
                        <button type="submit" class="btn btn-primary">Save adjustment</button>
                    </div>
                </form>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const inventoryRoutes = {
            data: @json(route('admin.inventory.data')),
            lowStock: @json(route('admin.inventory.low-stock.data')),
            @can('inventory.adjust')
            adjust: @json(route('admin.inventory.variants.adjust', ['variantId' => '__ID__'])),
            @endcan
        };
        const canAdjustStock = @json(auth()->user()->can('inventory.adjust'));
        @can('inventory.adjust')
        const csrfToken = @json(csrf_token());

        function routeFor(template, id) {
            return template.replace('__ID__', id);
        }
        @endcan

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function stockStatus(stock) {
            if (!stock.track_inventory) {
                return '<span class="badge text-bg-info">Untracked</span>';
            }

            if (stock.allow_backorder) {
                return '<span class="badge text-bg-primary">Backorder</span>';
            }

            if (stock.is_low_stock) {
                return '<span class="badge text-bg-warning">Low stock</span>';
            }

            return '<span class="badge text-bg-success">Healthy</span>';
        }

        function renderStocks(stocks) {
            if (!stocks.length) {
                $('#stock-table-body').html(`<tr><td colspan="${canAdjustStock ? 8 : 7}" class="text-center py-4 text-secondary">No stock records found.</td></tr>`);
                return;
            }

            $('#stock-table-body').html(stocks.map((stock) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(stock.product_name || 'Unassigned product')}</td>
                    <td>${escapeHtml(stock.variant_name || 'Default')}</td>
                    <td>${escapeHtml(stock.sku || '')}</td>
                    <td>${stock.quantity_on_hand}</td>
                    <td>${stock.quantity_reserved}</td>
                    <td>${stock.available_quantity}</td>
                    <td>${stockStatus(stock)}</td>
                    ${canAdjustStock ? `
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-action="adjust"
                                data-variant-id="${stock.product_variant_id}"
                                data-label="${escapeHtml(`${stock.product_name || 'Product'} / ${stock.variant_name || 'Default'} / ${stock.sku || ''}`)}"
                            >Adjust</button>
                        </td>
                    ` : ''}
                </tr>
            `).join(''));
        }

        function renderLowStock(stocks) {
            if (!stocks.length) {
                $('#low-stock-table-body').html('<tr><td colspan="3" class="text-center py-4 text-secondary">No low-stock items.</td></tr>');
                return;
            }

            $('#low-stock-table-body').html(stocks.map((stock) => `
                <tr>
                    <td>
                        <span class="fw-semibold d-block">${escapeHtml(stock.product_name || 'Unassigned product')}</span>
                        <span class="text-secondary">${escapeHtml(stock.variant_name || 'Default')} ${stock.sku ? `/ ${escapeHtml(stock.sku)}` : ''}</span>
                    </td>
                    <td>${stock.available_quantity}</td>
                    <td>${stock.low_stock_threshold}</td>
                </tr>
            `).join(''));
        }

        function loadStocks() {
            return $.ajax({
                url: inventoryRoutes.data,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                renderStocks(body.data);
            }).catch(() => {
                adminToast('Unable to load inventory stocks.', 'danger');
            });
        }

        function loadLowStock() {
            return $.ajax({
                url: inventoryRoutes.lowStock,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                renderLowStock(body.data);
            }).catch(() => {
                adminToast('Unable to load low-stock items.', 'danger');
            });
        }

        $('#refresh-stocks').on('click', loadStocks);
        $('#refresh-low-stock').on('click', loadLowStock);
        @can('inventory.adjust')
        $('#stock-table-body').on('click', 'button[data-action="adjust"]', function () {
            $('#adjust_variant_id').val($(this).data('variant-id'));
            $('#adjust_variant_label').val($(this).data('label'));
            $('#quantity_delta').trigger('focus');
        });
        $('#reset-adjustment-form').on('click', function () {
            $('#stock-adjustment-form')[0].reset();
            $('#adjust_variant_id').val('');
            $('#adjust_variant_label').val('Select a stock row');
        });
        $('#stock-adjustment-form').on('submit', function (event) {
            event.preventDefault();

            const variantId = $('#adjust_variant_id').val();

            if (!variantId) {
                adminToast('Select a stock row before adjusting.', 'warning');
                return;
            }

            $.ajax({
                url: routeFor(inventoryRoutes.adjust, variantId),
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({
                    quantity_delta: $('#quantity_delta').val(),
                    note: $('#adjustment_note').val(),
                }),
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Stock adjusted successfully.');
                $('#quantity_delta').val('');
                $('#adjustment_note').val('');
                return $.when(loadStocks(), loadLowStock());
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to adjust stock.', 'danger');
            });
        });
        @endcan
        loadStocks();
        loadLowStock();
    </script>
@endpush
