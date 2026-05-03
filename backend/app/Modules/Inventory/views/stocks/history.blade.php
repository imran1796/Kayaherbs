@extends('admin.layouts.app')

@section('title', 'Inventory History')
@section('page_title', 'Inventory History')
@section('page_subtitle', 'Review stock adjustments, reservations, and releases.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active" aria-current="page">History</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title">Stock Movement History</h3>
                    <p class="text-secondary mb-0 mt-1">Latest inventory movement records.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary">Stock list</a>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-history">Refresh</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th>Type</th>
                            <th>Delta</th>
                            <th>On hand after</th>
                            <th>Reserved after</th>
                            <th>Actor</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-history-table-body">
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">Loading history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const inventoryHistoryRoutes = {
            data: @json(route('admin.inventory.history.data')),
        };

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function typeBadge(type) {
            const badge = type === 'adjustment' ? 'secondary' : (type === 'reservation' ? 'warning' : 'success');

            return `<span class="badge text-bg-${badge}">${escapeHtml(type)}</span>`;
        }

        function renderHistory(rows) {
            if (!rows.length) {
                $('#inventory-history-table-body').html('<tr><td colspan="10" class="text-center py-4 text-secondary">No inventory history found.</td></tr>');
                return;
            }

            $('#inventory-history-table-body').html(rows.map((row) => `
                <tr>
                    <td>${escapeHtml(row.created_at || '')}</td>
                    <td class="fw-semibold">${escapeHtml(row.product_name || 'Unassigned product')}</td>
                    <td>${escapeHtml(row.variant_name || 'Default')}</td>
                    <td>${escapeHtml(row.sku || '')}</td>
                    <td>${typeBadge(row.type)}</td>
                    <td>${row.quantity_delta}</td>
                    <td>${row.quantity_on_hand_after}</td>
                    <td>${row.quantity_reserved_after}</td>
                    <td>${escapeHtml(row.actor_name || 'System')}</td>
                    <td>${escapeHtml(row.note || '')}</td>
                </tr>
            `).join(''));
        }

        function loadHistory() {
            return $.ajax({
                url: inventoryHistoryRoutes.data,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                renderHistory(body.data);
            }).catch(() => {
                adminToast('Unable to load inventory history.', 'danger');
            });
        }

        $('#refresh-history').on('click', loadHistory);
        loadHistory();
    </script>
@endpush
