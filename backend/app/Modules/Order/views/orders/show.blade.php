@extends('admin.layouts.app')

@section('title', 'Order Detail')
@section('page_title', 'Order Detail')
@section('page_subtitle', 'Review lifecycle timeline and update order status.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Orders</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Back to orders</a>
        <button type="button" class="btn btn-outline-primary" id="refresh-order-detail">Refresh</button>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body" id="order-summary-panel">
                    <p class="text-secondary mb-0">Loading order...</p>
                </div>
            </div>

            @can('orders.update_status')
                <form class="card" id="order-status-form">
                    <div class="card-header">
                        <h3 class="card-title">Change Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="order_status" class="form-label">Next status</label>
                            <select id="order_status" class="form-select" required>
                                <option value="">Select status</option>
                            </select>
                        </div>
                        <div>
                            <label for="order_status_note" class="form-label">Note</label>
                            <textarea id="order_status_note" rows="3" class="form-control" placeholder="Reason or operational note"></textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Update status</button>
                    </div>
                </form>
            @endcan

            @canany(['orders.cancel', 'orders.returns.create'])
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Cancellation And Returns</h3>
                    </div>
                    <div class="card-body">
                        @can('orders.cancel')
                            <form id="order-cancel-form" class="mb-3">
                                <label for="order_cancel_reason" class="form-label">Cancellation reason</label>
                                <textarea id="order_cancel_reason" rows="3" class="form-control mb-2" required></textarea>
                                <button type="submit" class="btn btn-outline-danger">Cancel order</button>
                            </form>
                        @endcan

                        @can('orders.returns.create')
                            <form id="order-return-form">
                                <label for="order_return_reason" class="form-label">Return reason</label>
                                <textarea id="order_return_reason" rows="3" class="form-control mb-2" required></textarea>
                                <button type="submit" class="btn btn-outline-primary">Create return request</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endcanany

            @canany(['orders.invoices.generate', 'orders.packing_slips.generate'])
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Documents</h3>
                    </div>
                    <div class="card-body" id="order-documents-panel">
                        <p class="text-secondary mb-0">Loading documents...</p>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2">
                        @can('orders.invoices.generate')
                            <button type="button" class="btn btn-outline-primary" id="generate-invoice">Generate invoice</button>
                        @endcan
                        @can('orders.packing_slips.generate')
                            <button type="button" class="btn btn-outline-primary" id="generate-packing-slip">Generate packing slip</button>
                        @endcan
                    </div>
                </div>
            @endcanany

            @canany(['payments.update', 'payments.cod.collect'])
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Payment Controls</h3>
                    </div>
                    <div class="card-body" id="payment-controls-panel">
                        <p class="text-secondary mb-0">Loading payments...</p>
                    </div>
                </div>
            @endcanany
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Timeline</h3>
                </div>
                <div class="card-body" id="order-timeline-panel">
                    <p class="text-secondary mb-0">Loading timeline...</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Items</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="order-items-table-body">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">Loading items...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer</h3>
                </div>
                <div class="card-body" id="order-customer-panel">
                    <p class="text-secondary mb-0">Loading customer...</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Return Requests</h3>
                </div>
                <div class="card-body" id="order-return-requests-panel">
                    <p class="text-secondary mb-0">Loading return requests...</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Shipments And Tracking</h3>
                </div>
                @can('orders.shipments.create')
                    <div class="card-body border-bottom">
                        <form id="order-shipment-form" class="row g-2">
                            <div class="col-md-4">
                                <label for="shipment_carrier_name" class="form-label">Carrier</label>
                                <input id="shipment_carrier_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="shipment_tracking_number" class="form-label">Tracking number</label>
                                <input id="shipment_tracking_number" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="shipment_status" class="form-label">Shipment status</label>
                                <select id="shipment_status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="shipped">Shipped</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="shipment_tracking_url" class="form-label">Tracking URL</label>
                                <input id="shipment_tracking_url" type="url" class="form-control">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Save shipment</button>
                            </div>
                        </form>
                    </div>
                @endcan
                <div class="card-body" id="order-shipments-panel">
                    <p class="text-secondary mb-0">Loading shipments...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const orderDetailRoutes = {
            data: @json(route('admin.orders.show.data', ['id' => $orderId])),
            @can('orders.update_status')
            status: @json(route('admin.orders.status.update', ['id' => $orderId])),
            @endcan
            @can('orders.cancel')
            cancel: @json(route('admin.orders.cancel', ['id' => $orderId])),
            @endcan
            @can('orders.returns.create')
            returnRequest: @json(route('admin.orders.return-requests.store', ['id' => $orderId])),
            @endcan
            @can('orders.shipments.create')
            shipment: @json(route('admin.orders.shipments.store', ['id' => $orderId])),
            @endcan
            @can('orders.invoices.generate')
            invoice: @json(route('admin.orders.invoice.generate', ['id' => $orderId])),
            invoicePrint: @json(route('admin.orders.invoice.print', ['id' => $orderId])),
            @endcan
            @can('orders.packing_slips.generate')
            packingSlip: @json(route('admin.orders.packing-slip.generate', ['id' => $orderId])),
            packingSlipPrint: @json(route('admin.orders.packing-slip.print', ['id' => $orderId])),
            @endcan
        };
        const paymentRoutes = {
            @can('payments.update')
            status: @json(route('admin.payments.status.update', ['id' => '__PAYMENT__'])),
            @endcan
            @can('payments.cod.collect')
            codCollect: @json(route('admin.payments.cod.collect', ['id' => '__PAYMENT__'])),
            @endcan
        };
        const csrfToken = @json(csrf_token());
        const orderTransitions = @json(\App\Modules\Order\Support\OrderStatus::transitions());
        const paymentTransitions = @json(\App\Modules\Payment\Support\PaymentStatus::transitions());
        let currentOrder = null;

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

            if (['cancelled', 'failed', 'failed_delivery', 'refunded'].includes(status)) {
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

        function labelStatus(status) {
            return String(status || '').replaceAll('_', ' ');
        }

        function renderSummary(order) {
            $('#order-summary-panel').html(`
                <h4 class="h5 mb-2">${escapeHtml(order.order_number)}</h4>
                <dl class="row mb-0">
                    <dt class="col-5">Status</dt>
                    <dd class="col-7">${statusBadge(order.status)}</dd>
                    <dt class="col-5">Payment</dt>
                    <dd class="col-7">${statusBadge(order.payment_status)}</dd>
                    <dt class="col-5">Fulfillment</dt>
                    <dd class="col-7">${statusBadge(order.fulfillment_status)}</dd>
                    <dt class="col-5">Total</dt>
                    <dd class="col-7">${escapeHtml(order.totals?.grand_total || '')} ${escapeHtml(order.totals?.currency || '')}</dd>
                    <dt class="col-5">Placed</dt>
                    <dd class="col-7">${escapeHtml(order.lifecycle?.placed_at || order.created_at || '')}</dd>
                </dl>
            `);
        }

        function renderTimeline(order) {
            const histories = order.status_history || [];

            if (!histories.length) {
                $('#order-timeline-panel').html('<p class="text-secondary mb-0">No timeline entries found.</p>');
                return;
            }

            $('#order-timeline-panel').html(histories.map((entry) => `
                <div class="border-start border-3 ps-3 pb-3">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="fw-semibold">${entry.from_status ? `${escapeHtml(labelStatus(entry.from_status))} to ` : ''}${escapeHtml(labelStatus(entry.to_status))}</div>
                        <span class="text-secondary">${escapeHtml(entry.created_at || '')}</span>
                    </div>
                    <div class="text-secondary">${escapeHtml(entry.actor?.name || 'System')}</div>
                    ${entry.note ? `<p class="mb-0 mt-1">${escapeHtml(entry.note)}</p>` : ''}
                </div>
            `).join(''));
        }

        function renderItems(items) {
            if (!items.length) {
                $('#order-items-table-body').html('<tr><td colspan="5" class="text-center py-4 text-secondary">No items found.</td></tr>');
                return;
            }

            $('#order-items-table-body').html(items.map((item) => `
                <tr>
                    <td>
                        <span class="fw-semibold d-block">${escapeHtml(item.product_name || '')}</span>
                        <span class="text-secondary">${escapeHtml(item.variant_name || '')}</span>
                    </td>
                    <td>${escapeHtml(item.sku || '')}</td>
                    <td>${escapeHtml(item.quantity || '')}</td>
                    <td>${escapeHtml(item.unit_price || '')}</td>
                    <td>${escapeHtml(item.line_total || '')}</td>
                </tr>
            `).join(''));
        }

        function renderCustomer(customer) {
            if (!customer) {
                $('#order-customer-panel').html('<p class="text-secondary mb-0">No customer linked.</p>');
                return;
            }

            $('#order-customer-panel').html(`
                <h4 class="h6 mb-1">${escapeHtml(customer.name)}</h4>
                <p class="text-secondary mb-0">${escapeHtml(customer.email)}</p>
            `);
        }

        function renderReturnRequests(returnRequests) {
            if (!returnRequests.length) {
                $('#order-return-requests-panel').html('<p class="text-secondary mb-0">No return requests.</p>');
                return;
            }

            $('#order-return-requests-panel').html(returnRequests.map((request) => `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <span class="fw-semibold">${statusBadge(request.status)}</span>
                        <span class="text-secondary">${escapeHtml(request.created_at || '')}</span>
                    </div>
                    <p class="mb-1 mt-1">${escapeHtml(request.reason || '')}</p>
                    <small class="text-secondary">${escapeHtml(request.requested_by?.name || 'System')}</small>
                </div>
            `).join(''));
        }

        function renderShipments(shipments) {
            if (!shipments.length) {
                $('#order-shipments-panel').html('<p class="text-secondary mb-0">No shipments linked.</p>');
                return;
            }

            $('#order-shipments-panel').html(shipments.map((shipment) => `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <span class="fw-semibold">${escapeHtml(shipment.carrier_name || '')}</span>
                        ${statusBadge(shipment.status)}
                    </div>
                    <div class="text-secondary">${escapeHtml(shipment.tracking_number || 'No tracking number')}</div>
                    ${shipment.tracking_url ? `<a href="${escapeHtml(shipment.tracking_url)}" target="_blank" rel="noopener">Tracking link</a>` : ''}
                    <div class="small text-secondary mt-1">Created by ${escapeHtml(shipment.created_by?.name || 'System')} ${shipment.shipped_at ? `- Shipped ${escapeHtml(shipment.shipped_at)}` : ''}</div>
                </div>
            `).join(''));
        }

        function renderDocuments(order) {
            const invoice = order.invoice;
            const packingSlip = order.packing_slip;

            $('#order-documents-panel').html(`
                <dl class="row mb-0">
                    <dt class="col-5">Invoice</dt>
                    <dd class="col-7">
                        ${invoice ? `${escapeHtml(invoice.invoice_number)} ${statusBadge(invoice.status)} <a class="ms-2" href="${orderDetailRoutes.invoicePrint}" target="_blank" rel="noopener">Print</a>` : '<span class="text-secondary">Not generated</span>'}
                    </dd>
                    <dt class="col-5">Issued</dt>
                    <dd class="col-7">${escapeHtml(invoice?.issued_at || '')}</dd>
                    <dt class="col-5">Packing slip</dt>
                    <dd class="col-7">
                        ${packingSlip ? `${escapeHtml(packingSlip.packing_slip_number)} ${statusBadge(packingSlip.status)} <a class="ms-2" href="${orderDetailRoutes.packingSlipPrint}" target="_blank" rel="noopener">Print</a>` : '<span class="text-secondary">Not generated</span>'}
                    </dd>
                    <dt class="col-5">Generated</dt>
                    <dd class="col-7">${escapeHtml(packingSlip?.generated_at || '')}</dd>
                </dl>
            `);
        }

        function paymentRouteFor(template, id) {
            return template.replace('__PAYMENT__', id);
        }

        function renderPaymentControls(payments) {
            if (!payments.length) {
                $('#payment-controls-panel').html('<p class="text-secondary mb-0">No payments found.</p>');
                return;
            }

            $('#payment-controls-panel').html(payments.map((payment) => {
                const allowedStatuses = paymentTransitions[payment.status] || [];
                const options = allowedStatuses.map((status) => `<option value="${escapeHtml(status)}">${escapeHtml(labelStatus(status))}</option>`).join('');
                const canCollectCod = payment.provider === 'cod' && payment.cod_status !== 'collected' && payment.status !== 'paid';

                return `
                    <div class="border-bottom pb-3 mb-3" data-payment-id="${payment.id}">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">${escapeHtml(payment.method_name || payment.provider)}</div>
                                <div class="text-secondary">${escapeHtml(payment.amount)} ${escapeHtml(payment.currency)} ${payment.transaction_id ? `- ${escapeHtml(payment.transaction_id)}` : ''}</div>
                            </div>
                            <div>${statusBadge(payment.status)} ${payment.cod_status ? statusBadge(payment.cod_status) : ''}</div>
                        </div>
                        @can('payments.update')
                            <form class="payment-status-form row g-2 mb-2">
                                <div class="col-md-4">
                                    <select class="form-select payment-status" ${allowedStatuses.length ? '' : 'disabled'} required>
                                        <option value="">Next status</option>
                                        ${options}
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input class="form-control payment-transaction-id" placeholder="Transaction ID">
                                </div>
                                <div class="col-md-3">
                                    <input class="form-control payment-provider-reference" placeholder="Reference">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-outline-primary w-100" ${allowedStatuses.length ? '' : 'disabled'}>Update</button>
                                </div>
                            </form>
                        @endcan
                        @can('payments.cod.collect')
                            ${canCollectCod ? '<button type="button" class="btn btn-outline-success btn-sm collect-cod-payment">Collect COD</button>' : ''}
                        @endcan
                    </div>
                `;
            }).join(''));
        }

        function renderStatusOptions(order) {
            const allowed = orderTransitions[order.status] || [];
            const $status = $('#order_status');

            $status.html('<option value="">Select status</option>');
            allowed.forEach((status) => {
                $status.append(`<option value="${escapeHtml(status)}">${escapeHtml(labelStatus(status))}</option>`);
            });
            $status.prop('disabled', allowed.length === 0);
            $('#order-status-form button[type="submit"]').prop('disabled', allowed.length === 0);
        }

        function loadOrderDetail() {
            return $.ajax({
                url: orderDetailRoutes.data,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                currentOrder = body.data;
                renderSummary(currentOrder);
                renderTimeline(currentOrder);
                renderItems(currentOrder.items || []);
                renderCustomer(currentOrder.customer);
                renderReturnRequests(currentOrder.return_requests || []);
                renderShipments(currentOrder.shipments || []);
                renderDocuments(currentOrder);
                renderPaymentControls(currentOrder.payments || []);
                renderStatusOptions(currentOrder);
            }).catch(() => {
                adminToast('Unable to load order detail.', 'danger');
            });
        }

        $('#refresh-order-detail').on('click', loadOrderDetail);
        $('#order-status-form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: orderDetailRoutes.status,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({
                    _method: 'PATCH',
                    status: $('#order_status').val(),
                    note: $('#order_status_note').val(),
                }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Order status updated successfully.');
                $('#order_status_note').val('');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to update order status.', 'danger');
            });
        });
        $('#order-cancel-form').on('submit', function (event) {
            event.preventDefault();

            if (!confirm('Cancel this order?')) {
                return;
            }

            $.ajax({
                url: orderDetailRoutes.cancel,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ reason: $('#order_cancel_reason').val() }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Order cancelled successfully.');
                $('#order_cancel_reason').val('');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to cancel order.', 'danger');
            });
        });
        $('#order-return-form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: orderDetailRoutes.returnRequest,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ reason: $('#order_return_reason').val() }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Return request created successfully.');
                $('#order_return_reason').val('');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to create return request.', 'danger');
            });
        });
        $('#order-shipment-form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: orderDetailRoutes.shipment,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({
                    carrier_name: $('#shipment_carrier_name').val(),
                    tracking_number: $('#shipment_tracking_number').val(),
                    tracking_url: $('#shipment_tracking_url').val(),
                    status: $('#shipment_status').val(),
                }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Shipment linked successfully.');
                $('#order-shipment-form')[0].reset();
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to save shipment.', 'danger');
            });
        });
        $('#generate-invoice').on('click', function () {
            $.ajax({
                url: orderDetailRoutes.invoice,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({}),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Invoice generated successfully.');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to generate invoice.', 'danger');
            });
        });
        $('#generate-packing-slip').on('click', function () {
            $.ajax({
                url: orderDetailRoutes.packingSlip,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({}),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Packing slip generated successfully.');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to generate packing slip.', 'danger');
            });
        });
        $('#payment-controls-panel').on('submit', '.payment-status-form', function (event) {
            event.preventDefault();

            const $row = $(this).closest('[data-payment-id]');
            const paymentId = $row.data('payment-id');

            $.ajax({
                url: paymentRouteFor(paymentRoutes.status, paymentId),
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({
                    _method: 'PATCH',
                    status: $row.find('.payment-status').val(),
                    transaction_id: $row.find('.payment-transaction-id').val(),
                    provider_reference: $row.find('.payment-provider-reference').val(),
                    metadata: { source: 'admin-order-detail' },
                }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Payment updated successfully.');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to update payment.', 'danger');
            });
        });
        $('#payment-controls-panel').on('click', '.collect-cod-payment', function () {
            const paymentId = $(this).closest('[data-payment-id]').data('payment-id');

            $.ajax({
                url: paymentRouteFor(paymentRoutes.codCollect, paymentId),
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ metadata: { source: 'admin-order-detail' } }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'COD payment collected successfully.');
                return loadOrderDetail();
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                adminToast(errors || 'Unable to collect COD payment.', 'danger');
            });
        });
        loadOrderDetail();
    </script>
@endpush
