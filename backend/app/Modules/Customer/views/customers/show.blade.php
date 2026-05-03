@extends('admin.layouts.app')

@section('title', 'Customer Detail')
@section('page_title', 'Customer Detail')
@section('page_subtitle', 'Review profile, support notes, addresses, and order history.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Back to customers</a>
        <button type="button" class="btn btn-outline-primary" id="refresh-customer-detail">Refresh</button>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile</h3>
                </div>
                <div class="card-body" id="customer-profile-panel">
                    <p class="text-secondary mb-0">Loading profile...</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Tags</h3>
                </div>
                <div class="card-body" id="customer-tags-panel">
                    <p class="text-secondary mb-0">Loading tags...</p>
                </div>
                @can('customers.tags.update')
                    <div class="card-footer">
                        <form id="customer-tags-form">
                            <label for="customer_tags" class="form-label">Tags</label>
                            <input id="customer_tags" class="form-control mb-2" placeholder="vip, wholesale, follow-up">
                            <button type="submit" class="btn btn-primary">Save tags</button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <h3 class="card-title mb-0">Addresses</h3>
                        @can('customers.update')
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-address-form">New address</button>
                        @endcan
                    </div>
                </div>
                @can('customers.update')
                    <div class="card-body border-bottom">
                        <form id="customer-address-form" class="row g-2">
                            <input type="hidden" id="address_id">
                            <div class="col-md-4">
                                <label for="address_label" class="form-label">Label</label>
                                <input id="address_label" class="form-control" placeholder="Home">
                            </div>
                            <div class="col-md-4">
                                <label for="address_recipient_name" class="form-label">Recipient</label>
                                <input id="address_recipient_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="address_phone" class="form-label">Phone</label>
                                <input id="address_phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="address_line_1" class="form-label">Address line 1</label>
                                <input id="address_line_1" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="address_line_2" class="form-label">Address line 2</label>
                                <input id="address_line_2" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="address_city" class="form-label">City</label>
                                <input id="address_city" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="address_state" class="form-label">State</label>
                                <input id="address_state" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label for="address_postal_code" class="form-label">Postal</label>
                                <input id="address_postal_code" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label for="address_country" class="form-label">Country</label>
                                <input id="address_country" class="form-control" value="BD" maxlength="2">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input id="is_default_shipping" class="form-check-input" type="checkbox">
                                    <label for="is_default_shipping" class="form-check-label">Default shipping</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input id="is_default_billing" class="form-check-input" type="checkbox">
                                    <label for="is_default_billing" class="form-check-label">Default billing</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="clear-address-form">Clear</button>
                                <button type="submit" class="btn btn-primary" id="address-submit-button">Save address</button>
                            </div>
                        </form>
                    </div>
                @endcan
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Recipient</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Default</th>
                                    @can('customers.update')
                                        <th class="text-end">Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody id="customer-address-table-body">
                                <tr>
                                    <td colspan="@can('customers.update') 6 @else 5 @endcan" class="text-center py-4 text-secondary">Loading addresses...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <h3 class="card-title mb-0">Order History</h3>
                        <span class="badge text-bg-secondary" id="customer-order-count">0 orders</span>
                    </div>
                </div>
                <div class="card-body border-bottom" id="customer-order-history-panel">
                    <p class="text-secondary mb-0">Loading order history...</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Fulfillment</th>
                                    <th>Method</th>
                                    <th>Total</th>
                                    <th>Placed</th>
                                </tr>
                            </thead>
                            <tbody id="customer-order-table-body">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-secondary">Loading orders...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Internal Notes</h3>
                </div>
                @can('customers.notes.create')
                    <div class="card-body border-bottom">
                        <form id="customer-note-form">
                            <label for="customer_note" class="form-label">New note</label>
                            <textarea id="customer_note" rows="3" class="form-control mb-2" required></textarea>
                            <button type="submit" class="btn btn-primary">Add note</button>
                        </form>
                    </div>
                @endcan
                <div class="card-body" id="customer-notes-panel">
                    <p class="text-secondary mb-0">Loading notes...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const customerDetailRoutes = {
            data: @json(route('admin.customers.show.data', ['id' => $customerId])),
            @can('customers.update')
            addressStore: @json(route('admin.customers.addresses.store', ['id' => $customerId])),
            addressUpdate: @json(route('admin.customers.addresses.update', ['id' => $customerId, 'addressId' => '__ADDRESS__'])),
            addressDestroy: @json(route('admin.customers.addresses.destroy', ['id' => $customerId, 'addressId' => '__ADDRESS__'])),
            @endcan
            @can('customers.notes.create')
            noteStore: @json(route('admin.customers.notes.store', ['id' => $customerId])),
            @endcan
            @can('customers.tags.update')
            tagsSync: @json(route('admin.customers.tags.sync', ['id' => $customerId])),
            @endcan
        };
        const canManageAddresses = @json(auth()->user()->can('customers.update'));
        const csrfToken = @json(csrf_token());
        let currentCustomer = null;

        function routeFor(template, token, value) {
            return template.replace(token, value);
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

        function renderProfile(customer) {
            $('#customer-profile-panel').html(`
                <h4 class="h5 mb-2">${escapeHtml(customer.name)}</h4>
                <dl class="row mb-0">
                    <dt class="col-4">Email</dt>
                    <dd class="col-8">${escapeHtml(customer.email)}</dd>
                    <dt class="col-4">Phone</dt>
                    <dd class="col-8">${escapeHtml(customer.phone || '')}</dd>
                    <dt class="col-4">Status</dt>
                    <dd class="col-8">${statusBadge(customer.status)}</dd>
                    <dt class="col-4">Orders</dt>
                    <dd class="col-8">${customer.orders_count ?? 0}</dd>
                    <dt class="col-4">Joined</dt>
                    <dd class="col-8">${escapeHtml(customer.created_at || '')}</dd>
                </dl>
            `);
        }

        function renderTags(tags) {
            if (!tags.length) {
                $('#customer-tags-panel').html('<p class="text-secondary mb-0">No tags yet.</p>');
                $('#customer_tags').val('');
                return;
            }

            $('#customer-tags-panel').html(tags.map((tag) => `<span class="badge text-bg-secondary me-1 mb-1">${escapeHtml(tag.tag)}</span>`).join(''));
            $('#customer_tags').val(tags.map((tag) => tag.tag).join(', '));
        }

        function renderAddresses(addresses) {
            if (!addresses.length) {
                $('#customer-address-table-body').html(`<tr><td colspan="${canManageAddresses ? 6 : 5}" class="text-center py-4 text-secondary">No addresses found.</td></tr>`);
                return;
            }

            $('#customer-address-table-body').html(addresses.map((address) => `
                <tr>
                    <td>${escapeHtml(address.label || '')}</td>
                    <td>${escapeHtml(address.recipient_name || '')}</td>
                    <td>${escapeHtml(address.phone || '')}</td>
                    <td>${escapeHtml([address.address_line_1, address.city, address.country].filter(Boolean).join(', '))}</td>
                    <td>${address.is_default_shipping ? '<span class="badge text-bg-success">Shipping</span>' : ''} ${address.is_default_billing ? '<span class="badge text-bg-info">Billing</span>' : ''}</td>
                    ${canManageAddresses ? `
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit-address" data-id="${address.id}">Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-address" data-id="${address.id}">Delete</button>
                        </td>
                    ` : ''}
                </tr>
            `).join(''));
        }

        function renderOrders(orders) {
            const orderCount = currentCustomer?.orders_count ?? orders.length;
            $('#customer-order-count').text(`${orderCount} ${Number(orderCount) === 1 ? 'order' : 'orders'}`);

            if (!orders.length) {
                $('#customer-order-history-panel').html('<p class="text-secondary mb-0">This customer has not placed any orders yet.</p>');
                $('#customer-order-table-body').html('<tr><td colspan="7" class="text-center py-4 text-secondary">No orders found.</td></tr>');
                return;
            }

            const totalSpent = orders.reduce((sum, order) => sum + Number(order.totals?.grand_total || 0), 0);
            const currency = orders[0]?.totals?.currency || '';
            const latestOrder = orders[0];

            $('#customer-order-history-panel').html(`
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="text-secondary small">Latest order</div>
                        <div class="fw-semibold">${escapeHtml(latestOrder.order_number || '')}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small">Latest status</div>
                        <div class="fw-semibold">${escapeHtml(latestOrder.status || '')}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small">Visible total</div>
                        <div class="fw-semibold">${escapeHtml(totalSpent.toFixed(2))} ${escapeHtml(currency)}</div>
                    </div>
                </div>
            `);

            $('#customer-order-table-body').html(orders.map((order) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(order.order_number)}</td>
                    <td>${escapeHtml(order.status)}</td>
                    <td>${escapeHtml(order.payment_status)}</td>
                    <td>${escapeHtml(order.fulfillment_status)}</td>
                    <td>${escapeHtml(order.payment_method?.name || '')}</td>
                    <td>${escapeHtml(order.totals?.grand_total || '')} ${escapeHtml(order.totals?.currency || '')}</td>
                    <td>${escapeHtml(order.placed_at || '')}</td>
                </tr>
            `).join(''));
        }

        function renderNotes(notes) {
            if (!notes.length) {
                $('#customer-notes-panel').html('<p class="text-secondary mb-0">No notes yet.</p>');
                return;
            }

            $('#customer-notes-panel').html(notes.map((note) => `
                <div class="border-bottom pb-2 mb-2">
                    <p class="mb-1">${escapeHtml(note.note)}</p>
                    <small class="text-secondary">${escapeHtml(note.author?.name || 'System')} - ${escapeHtml(note.created_at || '')}</small>
                </div>
            `).join(''));
        }

        function loadCustomerDetail() {
            return $.ajax({
                url: customerDetailRoutes.data,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((body) => {
                const customer = body.data;
                currentCustomer = customer;

                renderProfile(customer);
                renderTags(customer.tags || []);
                renderAddresses(customer.addresses || []);
                renderOrders(customer.orders || []);
                renderNotes(customer.notes || []);
            }).catch(() => {
                adminToast('Unable to load customer detail.', 'danger');
            });
        }

        function addressPayload() {
            return {
                label: $('#address_label').val(),
                recipient_name: $('#address_recipient_name').val(),
                phone: $('#address_phone').val(),
                address_line_1: $('#address_line_1').val(),
                address_line_2: $('#address_line_2').val(),
                city: $('#address_city').val(),
                state: $('#address_state').val(),
                postal_code: $('#address_postal_code').val(),
                country: $('#address_country').val() || 'BD',
                is_default_shipping: $('#is_default_shipping').is(':checked'),
                is_default_billing: $('#is_default_billing').is(':checked'),
            };
        }

        function clearAddressForm() {
            $('#customer-address-form')[0]?.reset();
            $('#address_id').val('');
            $('#address_country').val('BD');
            $('#address-submit-button').text('Save address');
        }

        function setAddressForm(address) {
            $('#address_id').val(address.id);
            $('#address_label').val(address.label || '');
            $('#address_recipient_name').val(address.recipient_name || '');
            $('#address_phone').val(address.phone || '');
            $('#address_line_1').val(address.address_line_1 || '');
            $('#address_line_2').val(address.address_line_2 || '');
            $('#address_city').val(address.city || '');
            $('#address_state').val(address.state || '');
            $('#address_postal_code').val(address.postal_code || '');
            $('#address_country').val(address.country || 'BD');
            $('#is_default_shipping').prop('checked', !!address.is_default_shipping);
            $('#is_default_billing').prop('checked', !!address.is_default_billing);
            $('#address-submit-button').text('Update address');
        }

        $('#refresh-customer-detail').on('click', loadCustomerDetail);
        $('#reset-address-form, #clear-address-form').on('click', clearAddressForm);
        $('#customer-address-table-body').on('click', 'button[data-action]', function () {
            const action = $(this).data('action');
            const id = $(this).data('id');

            if (action === 'edit-address') {
                const address = (currentCustomer?.addresses || []).find((item) => Number(item.id) === Number(id));
                if (address) {
                    setAddressForm(address);
                }
                return;
            }

            if (!confirm('Delete this address?')) {
                return;
            }

            $.ajax({
                url: routeFor(customerDetailRoutes.addressDestroy, '__ADDRESS__', id),
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ _method: 'DELETE' }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then(() => {
                adminToast('Customer address deleted successfully.');
                clearAddressForm();
                return loadCustomerDetail();
            }).catch((xhr) => {
                adminToast(xhr.responseJSON?.message || 'Unable to delete address.', 'danger');
            });
        });
        $('#customer-address-form').on('submit', function (event) {
            event.preventDefault();

            const addressId = $('#address_id').val();
            const isUpdate = !!addressId;
            const url = isUpdate
                ? routeFor(customerDetailRoutes.addressUpdate, '__ADDRESS__', addressId)
                : customerDetailRoutes.addressStore;
            const payload = addressPayload();

            if (isUpdate) {
                payload._method = 'PUT';
            }

            $.ajax({
                url,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Customer address saved successfully.');
                clearAddressForm();
                return loadCustomerDetail();
            }).catch((xhr) => {
                const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join(' ') : xhr.responseJSON?.message;
                adminToast(errors || 'Unable to save address.', 'danger');
            });
        });
        $('#customer-note-form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: customerDetailRoutes.noteStore,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ note: $('#customer_note').val() }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Customer note added successfully.');
                $('#customer_note').val('');
                return loadCustomerDetail();
            }).catch((xhr) => {
                const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join(' ') : xhr.responseJSON?.message;
                adminToast(errors || 'Unable to add note.', 'danger');
            });
        });
        $('#customer-tags-form').on('submit', function (event) {
            event.preventDefault();

            const tags = $('#customer_tags').val().split(',').map((tag) => tag.trim()).filter(Boolean);

            $.ajax({
                url: customerDetailRoutes.tagsSync,
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ _method: 'PUT', tags }),
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((body) => {
                adminToast(body.message || 'Customer tags updated successfully.');
                return loadCustomerDetail();
            }).catch((xhr) => {
                const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join(' ') : xhr.responseJSON?.message;
                adminToast(errors || 'Unable to save tags.', 'danger');
            });
        });
        loadCustomerDetail();
    </script>
@endpush
