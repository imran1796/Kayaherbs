@extends('admin.layouts.app')

@section('title', 'Shipping')
@section('page_title', 'Shipping')
@section('page_subtitle', 'Manage delivery zones and customer-facing delivery rates.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Shipping</li>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card mb-3">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title">Delivery Zones</h3>
                            <p class="text-secondary mb-0 mt-1">Match rates to shipping country, city, state, or postal code.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-zones">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Country</th>
                                    <th>Cities</th>
                                    <th>Status</th>
                                    <th>Rates</th>
                                    @canany(['shipping.update', 'shipping.delete'])
                                        <th class="text-end">Actions</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody id="zones-table-body">
                                <tr>
                                    <td colspan="@canany(['shipping.update', 'shipping.delete']) 6 @else 5 @endcanany" class="text-center py-4 text-secondary">Loading delivery zones...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title">Shipping Rates</h3>
                            <p class="text-secondary mb-0 mt-1">Amounts used by checkout when the shipping address matches a zone.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-rates">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Zone</th>
                                    <th>Amount</th>
                                    <th>Order Range</th>
                                    <th>Status</th>
                                    @canany(['shipping.update', 'shipping.delete'])
                                        <th class="text-end">Actions</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody id="rates-table-body">
                                <tr>
                                    <td colspan="@canany(['shipping.update', 'shipping.delete']) 6 @else 5 @endcanany" class="text-center py-4 text-secondary">Loading delivery rates...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            @canany(['shipping.create', 'shipping.update'])
                <form id="zone-form" class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title" id="zone-form-title">Create Delivery Zone</h3>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="zone_id">

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="zone_name" class="form-label">Name</label>
                                <input id="zone_name" class="form-control" required>
                            </div>
                            <div class="col-md-5">
                                <label for="zone_code" class="form-label">Code</label>
                                <input id="zone_code" class="form-control" placeholder="auto-from-name">
                            </div>
                            <div class="col-md-4">
                                <label for="zone_country" class="form-label">Country</label>
                                <input id="zone_country" class="form-control" maxlength="2" value="BD">
                            </div>
                            <div class="col-md-4">
                                <label for="zone_status" class="form-label">Status</label>
                                <select id="zone_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="zone_sort_order" class="form-label">Sort</label>
                                <input id="zone_sort_order" type="number" min="0" step="1" class="form-control" value="0">
                            </div>
                            <div class="col-12">
                                <label for="zone_cities" class="form-label">Cities</label>
                                <input id="zone_cities" class="form-control" placeholder="Dhaka, Gazipur">
                            </div>
                            <div class="col-md-6">
                                <label for="zone_states" class="form-label">States</label>
                                <input id="zone_states" class="form-control" placeholder="Dhaka">
                            </div>
                            <div class="col-md-6">
                                <label for="zone_postal_codes" class="form-label">Postal codes</label>
                                <input id="zone_postal_codes" class="form-control" placeholder="1207, 1213">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="reset-zone-form">Reset</button>
                        <button type="submit" class="btn btn-primary">Save zone</button>
                    </div>
                </form>

                <form id="rate-form" class="card">
                    <div class="card-header">
                        <h3 class="card-title" id="rate-form-title">Create Shipping Rate</h3>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="rate_id">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="rate_delivery_zone_id" class="form-label">Delivery zone</label>
                                <select id="rate_delivery_zone_id" class="form-select" required></select>
                            </div>
                            <div class="col-md-7">
                                <label for="rate_name" class="form-label">Name</label>
                                <input id="rate_name" class="form-control" required>
                            </div>
                            <div class="col-md-5">
                                <label for="rate_code" class="form-label">Code</label>
                                <input id="rate_code" class="form-control" placeholder="auto-from-name">
                            </div>
                            <div class="col-md-4">
                                <label for="rate_amount" class="form-label">Amount</label>
                                <input id="rate_amount" type="number" min="0" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="rate_min_order_amount" class="form-label">Min order</label>
                                <input id="rate_min_order_amount" type="number" min="0" step="0.01" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="rate_max_order_amount" class="form-label">Max order</label>
                                <input id="rate_max_order_amount" type="number" min="0" step="0.01" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="rate_status" class="form-label">Status</label>
                                <select id="rate_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="rate_sort_order" class="form-label">Sort</label>
                                <input id="rate_sort_order" type="number" min="0" step="1" class="form-control" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="reset-rate-form">Reset</button>
                        <button type="submit" class="btn btn-primary">Save rate</button>
                    </div>
                </form>
            @else
                <div class="card">
                    <div class="card-body text-secondary">You can view shipping rules, but your role cannot change them.</div>
                </div>
            @endcanany
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const shippingRoutes = {
            zonesData: @json(route('admin.shipping.zones.data')),
            zonesStore: @json(route('admin.shipping.zones.store')),
            zonesUpdate: @json(route('admin.shipping.zones.update', ['id' => '__ID__'])),
            zonesDelete: @json(route('admin.shipping.zones.destroy', ['id' => '__ID__'])),
            ratesData: @json(route('admin.shipping.rates.data')),
            ratesStore: @json(route('admin.shipping.rates.store')),
            ratesUpdate: @json(route('admin.shipping.rates.update', ['id' => '__ID__'])),
            ratesDelete: @json(route('admin.shipping.rates.destroy', ['id' => '__ID__'])),
        };
        const csrfToken = @json(csrf_token());
        const permissions = {
            create: @json(auth()->user()->can('shipping.create')),
            update: @json(auth()->user()->can('shipping.update')),
            delete: @json(auth()->user()->can('shipping.delete')),
        };
        let deliveryZones = [];
        let deliveryRates = [];

        function routeFor(template, id) {
            return template.replace('__ID__', id);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function listValue(items) {
            return (items || []).join(', ');
        }

        function splitList(value) {
            return String(value || '')
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);
        }

        function statusBadge(status) {
            return status === 'active'
                ? '<span class="badge text-bg-success">Active</span>'
                : '<span class="badge text-bg-secondary">Inactive</span>';
        }

        function renderZones() {
            const actionCol = permissions.update || permissions.delete;

            if (!deliveryZones.length) {
                $('#zones-table-body').html(`<tr><td colspan="${actionCol ? 6 : 5}" class="text-center py-4 text-secondary">No delivery zones found.</td></tr>`);
                renderZoneOptions();
                return;
            }

            $('#zones-table-body').html(deliveryZones.map((zone) => `
                <tr>
                    <td>
                        <span class="fw-semibold d-block">${escapeHtml(zone.name)}</span>
                        <span class="text-secondary">${escapeHtml(zone.code)}</span>
                    </td>
                    <td>${escapeHtml(zone.country)}</td>
                    <td>${escapeHtml(listValue(zone.cities) || 'Any')}</td>
                    <td>${statusBadge(zone.status)}</td>
                    <td>${zone.rates ? zone.rates.length : 0}</td>
                    ${actionCol ? `
                        <td class="text-end">
                            ${permissions.update ? `<button type="button" class="btn btn-sm btn-outline-primary" data-zone-action="edit" data-id="${zone.id}">Edit</button>` : ''}
                            ${permissions.delete ? `<button type="button" class="btn btn-sm btn-outline-danger" data-zone-action="delete" data-id="${zone.id}">Delete</button>` : ''}
                        </td>
                    ` : ''}
                </tr>
            `).join(''));

            renderZoneOptions();
        }

        function renderZoneOptions() {
            const current = $('#rate_delivery_zone_id').val();
            const options = deliveryZones.map((zone) => `<option value="${zone.id}">${escapeHtml(zone.name)} (${escapeHtml(zone.code)})</option>`).join('');
            $('#rate_delivery_zone_id').html(options || '<option value="">Create a zone first</option>');

            if (current && deliveryZones.some((zone) => String(zone.id) === String(current))) {
                $('#rate_delivery_zone_id').val(current);
            }
        }

        function renderRates() {
            const actionCol = permissions.update || permissions.delete;

            if (!deliveryRates.length) {
                $('#rates-table-body').html(`<tr><td colspan="${actionCol ? 6 : 5}" class="text-center py-4 text-secondary">No delivery rates found.</td></tr>`);
                return;
            }

            $('#rates-table-body').html(deliveryRates.map((rate) => {
                const range = `${rate.min_order_amount || '0.00'} - ${rate.max_order_amount || 'Any'}`;

                return `
                    <tr>
                        <td>
                            <span class="fw-semibold d-block">${escapeHtml(rate.name)}</span>
                            <span class="text-secondary">${escapeHtml(rate.code)}</span>
                        </td>
                        <td>${escapeHtml(rate.zone?.name || '')}</td>
                        <td>${escapeHtml(rate.amount)}</td>
                        <td>${escapeHtml(range)}</td>
                        <td>${statusBadge(rate.status)}</td>
                        ${actionCol ? `
                            <td class="text-end">
                                ${permissions.update ? `<button type="button" class="btn btn-sm btn-outline-primary" data-rate-action="edit" data-id="${rate.id}">Edit</button>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn btn-sm btn-outline-danger" data-rate-action="delete" data-id="${rate.id}">Delete</button>` : ''}
                            </td>
                        ` : ''}
                    </tr>
                `;
            }).join(''));
        }

        function ajaxJson(options) {
            return $.ajax({
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                ...options,
            });
        }

        function loadZones() {
            return ajaxJson({
                url: shippingRoutes.zonesData,
                method: 'GET',
            }).then((body) => {
                deliveryZones = body.data || [];
                renderZones();
            }).catch(() => {
                adminToast('Unable to load delivery zones.', 'danger');
            });
        }

        function loadRates() {
            return ajaxJson({
                url: shippingRoutes.ratesData,
                method: 'GET',
            }).then((body) => {
                deliveryRates = body.data || [];
                renderRates();
            }).catch(() => {
                adminToast('Unable to load delivery rates.', 'danger');
            });
        }

        function resetZoneForm() {
            $('#zone-form')[0]?.reset();
            $('#zone_id').val('');
            $('#zone_country').val('BD');
            $('#zone_status').val('active');
            $('#zone_sort_order').val(0);
            $('#zone-form-title').text('Create Delivery Zone');
        }

        function resetRateForm() {
            $('#rate-form')[0]?.reset();
            $('#rate_id').val('');
            $('#rate_status').val('active');
            $('#rate_sort_order').val(0);
            $('#rate-form-title').text('Create Shipping Rate');
            renderZoneOptions();
        }

        function zonePayload() {
            return {
                name: $('#zone_name').val(),
                code: $('#zone_code').val(),
                country: $('#zone_country').val(),
                states: splitList($('#zone_states').val()),
                cities: splitList($('#zone_cities').val()),
                postal_codes: splitList($('#zone_postal_codes').val()),
                status: $('#zone_status').val(),
                sort_order: $('#zone_sort_order').val(),
            };
        }

        function ratePayload() {
            return {
                delivery_zone_id: $('#rate_delivery_zone_id').val(),
                name: $('#rate_name').val(),
                code: $('#rate_code').val(),
                amount: $('#rate_amount').val(),
                min_order_amount: $('#rate_min_order_amount').val() || null,
                max_order_amount: $('#rate_max_order_amount').val() || null,
                status: $('#rate_status').val(),
                sort_order: $('#rate_sort_order').val(),
            };
        }

        function showErrors(xhr, fallback) {
            const body = xhr.responseJSON || {};
            const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
            adminToast(errors || fallback, 'danger');
        }

        $('#refresh-zones').on('click', loadZones);
        $('#refresh-rates').on('click', loadRates);
        $('#reset-zone-form').on('click', resetZoneForm);
        $('#reset-rate-form').on('click', resetRateForm);

        $('#zones-table-body').on('click', 'button[data-zone-action="edit"]', function () {
            const zone = deliveryZones.find((item) => String(item.id) === String($(this).data('id')));

            if (!zone) {
                return;
            }

            $('#zone_id').val(zone.id);
            $('#zone_name').val(zone.name);
            $('#zone_code').val(zone.code);
            $('#zone_country').val(zone.country);
            $('#zone_states').val(listValue(zone.states));
            $('#zone_cities').val(listValue(zone.cities));
            $('#zone_postal_codes').val(listValue(zone.postal_codes));
            $('#zone_status').val(zone.status);
            $('#zone_sort_order').val(zone.sort_order);
            $('#zone-form-title').text('Update Delivery Zone');
            $('#zone_name').trigger('focus');
        });

        $('#rates-table-body').on('click', 'button[data-rate-action="edit"]', function () {
            const rate = deliveryRates.find((item) => String(item.id) === String($(this).data('id')));

            if (!rate) {
                return;
            }

            $('#rate_id').val(rate.id);
            $('#rate_delivery_zone_id').val(rate.delivery_zone_id);
            $('#rate_name').val(rate.name);
            $('#rate_code').val(rate.code);
            $('#rate_amount').val(rate.amount);
            $('#rate_min_order_amount').val(rate.min_order_amount || '');
            $('#rate_max_order_amount').val(rate.max_order_amount || '');
            $('#rate_status').val(rate.status);
            $('#rate_sort_order').val(rate.sort_order);
            $('#rate-form-title').text('Update Shipping Rate');
            $('#rate_name').trigger('focus');
        });

        $('#zones-table-body').on('click', 'button[data-zone-action="delete"]', function () {
            const id = $(this).data('id');

            if (!confirm('Delete this delivery zone and its rates?')) {
                return;
            }

            ajaxJson({
                url: routeFor(shippingRoutes.zonesDelete, id),
                method: 'DELETE',
            }).then((body) => {
                adminToast(body.message || 'Delivery zone deleted successfully.');
                resetZoneForm();
                resetRateForm();
                return $.when(loadZones(), loadRates());
            }).catch((xhr) => showErrors(xhr, 'Unable to delete delivery zone.'));
        });

        $('#rates-table-body').on('click', 'button[data-rate-action="delete"]', function () {
            const id = $(this).data('id');

            if (!confirm('Delete this delivery rate?')) {
                return;
            }

            ajaxJson({
                url: routeFor(shippingRoutes.ratesDelete, id),
                method: 'DELETE',
            }).then((body) => {
                adminToast(body.message || 'Delivery rate deleted successfully.');
                resetRateForm();
                return $.when(loadZones(), loadRates());
            }).catch((xhr) => showErrors(xhr, 'Unable to delete delivery rate.'));
        });

        $('#zone-form').on('submit', function (event) {
            event.preventDefault();

            const id = $('#zone_id').val();

            ajaxJson({
                url: id ? routeFor(shippingRoutes.zonesUpdate, id) : shippingRoutes.zonesStore,
                method: id ? 'PUT' : 'POST',
                data: JSON.stringify(zonePayload()),
            }).then((body) => {
                adminToast(body.message || 'Delivery zone saved successfully.');
                resetZoneForm();
                return $.when(loadZones(), loadRates());
            }).catch((xhr) => showErrors(xhr, 'Unable to save delivery zone.'));
        });

        $('#rate-form').on('submit', function (event) {
            event.preventDefault();

            const id = $('#rate_id').val();

            ajaxJson({
                url: id ? routeFor(shippingRoutes.ratesUpdate, id) : shippingRoutes.ratesStore,
                method: id ? 'PUT' : 'POST',
                data: JSON.stringify(ratePayload()),
            }).then((body) => {
                adminToast(body.message || 'Delivery rate saved successfully.');
                resetRateForm();
                return $.when(loadZones(), loadRates());
            }).catch((xhr) => showErrors(xhr, 'Unable to save delivery rate.'));
        });

        $.when(loadZones(), loadRates());
    </script>
@endpush
