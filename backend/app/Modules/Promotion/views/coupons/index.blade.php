@extends('admin.layouts.app')

@section('title', 'Coupons')
@section('page_title', 'Coupons')
@section('page_subtitle', '')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Coupons</li>
@endsection

@section('content')
    @cannot('coupons.create')
        <div class="alert alert-info">You can review coupons, but creating coupons requires the coupons.create permission.</div>
    @endcannot
    @cannot('coupons.update')
        <div class="alert alert-warning">Coupon editing and status changes require the coupons.update permission.</div>
    @endcannot

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Total coupons</div><div class="h4 mb-0" id="coupon-total-count">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Active coupons</div><div class="h4 mb-0" id="coupon-active-count">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Redemptions</div><div class="h4 mb-0" id="coupon-redemption-count">0</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-secondary">Discount total</div><div class="h4 mb-0" id="coupon-discount-total">0.00</div></div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="coupon-filter-form" class="row g-2">
                <div class="col-md-4">
                    <label for="coupon_search" class="form-label">Search</label>
                    <input id="coupon_search" class="form-control form-control-sm" placeholder="Code or name">
                </div>
                <div class="col-md-3">
                    <label for="coupon_status" class="form-label">Status</label>
                    <select id="coupon_status" class="form-select form-select-sm js-select2" data-placeholder="All statuses">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="coupon_per_page" class="form-label">Rows</label>
                    <select id="coupon_per_page" class="form-select form-select-sm js-select2" data-placeholder="Rows">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-coupon-filters">Reset</button>
                    <button type="button" class="btn btn-outline-primary" id="refresh-coupons">Refresh</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title">Coupon List</h3>
                            <!-- <p class="text-secondary mb-0 mt-1" id="coupon-list-summary">Loading coupons...</p> -->
                        </div>
                        @can('coupons.create')
                            <button type="button" class="btn btn-sm btn-outline-primary" id="new-coupon">New coupon</button>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Discount</th>
                                    <th>Minimum</th>
                                    <th>Lifecycle</th>
                                    <th>Schedule</th>
                                    <th>Usage</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="coupon-table-body">
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-secondary">Loading coupons...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-secondary" id="coupon-pagination-summary">Page 1</div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary" id="previous-coupon-page">Previous</button>
                        <button type="button" class="btn btn-outline-secondary" id="next-coupon-page">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <form id="coupon-form" class="card">
                <div class="card-header">
                    <h3 class="card-title" id="coupon-form-title">Create Coupon</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" id="coupon_id">
                    <div class="alert alert-danger d-none" id="coupon-form-errors"></div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" class="form-control form-control-sm" required>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label">Code</label>
                        <input id="code" class="form-control form-control-sm text-uppercase" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="discount_type" class="form-label">Type</label>
                            <select id="discount_type" class="form-select form-select-sm js-select2" data-placeholder="Select type">
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                                <option value="free_delivery">Free delivery</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="discount_value" class="form-label">Value</label>
                            <input id="discount_value" type="number" min="0" step="0.01" class="form-control form-control-sm" value="0">
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label for="minimum_order_value" class="form-label">Minimum</label>
                            <input id="minimum_order_value" type="number" min="0" step="0.01" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" class="form-select form-select-sm js-select2" data-placeholder="Select status">
                                <option value="inactive">Inactive</option>
                                <option value="active">Active</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label for="starts_at" class="form-label">Starts</label>
                            <input id="starts_at" type="text" class="form-control form-control-sm coupon-datetime" placeholder="Select start date & time">
                        </div>
                        <div class="col-md-6">
                            <label for="ends_at" class="form-label">Ends</label>
                            <input id="ends_at" type="text" class="form-control form-control-sm coupon-datetime" placeholder="Select end date & time">
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label for="usage_limit" class="form-label">Usage limit</label>
                            <input id="usage_limit" type="number" min="1" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label for="per_customer_usage_limit" class="form-label">Customer limit</label>
                            <input id="per_customer_usage_limit" type="number" min="1" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="eligible_product_ids" class="form-label">Products</label>
                        <select id="eligible_product_ids" class="form-select form-select-sm js-select2" multiple data-placeholder="All products">
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} #{{ $product->id }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <label for="eligible_category_ids" class="form-label">Categories</label>
                        <select id="eligible_category_ids" class="form-select form-select-sm js-select2" multiple data-placeholder="All categories">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }} #{{ $category->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="reset-coupon-form">Reset</button>
                    <button type="submit" class="btn btn-primary">Save coupon</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Coupon Usage Detail</h3>
                </div>
                <div class="card-body" id="coupon-usage-detail">
                    <p class="text-secondary mb-0">Select a coupon row to review usage detail.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top Coupon Performance</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th>Redemptions</th>
                                    <th>Discount</th>
                                </tr>
                            </thead>
                            <tbody id="coupon-performance-table-body">
                                <tr><td colspan="4" class="text-center py-4 text-secondary">Loading coupon performance...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">Recent Coupon Audit</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Code</th>
                            <th>Actor</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody id="coupon-audit-table-body">
                        <tr><td colspan="4" class="text-center py-4 text-secondary">Loading coupon audit events...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .select2-container--default .select2-selection--single {
            height: calc(1.8125rem + 2px);
            padding: .1rem .5rem;
            border: 1px solid #ced4da;
            border-radius: .2rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5rem;
            padding-left: 0;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.8125rem + 2px);
        }

        .select2-container--default .select2-selection--multiple {
            min-height: calc(1.8125rem + 2px);
            border: 1px solid #ced4da;
            border-radius: .2rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const couponReport = @json($couponReport);
        const couponAudits = @json($couponAudits);
        const couponRoutes = {
            data: @json(route('admin.coupons.data')),
            store: @json(route('admin.coupons.store')),
            show: @json(route('admin.coupons.show', ['id' => '__ID__'])),
            update: @json(route('admin.coupons.update', ['id' => '__ID__'])),
            activate: @json(route('admin.coupons.activate', ['id' => '__ID__'])),
            deactivate: @json(route('admin.coupons.deactivate', ['id' => '__ID__'])),
            destroy: @json(route('admin.coupons.destroy', ['id' => '__ID__'])),
        };
        const couponPermissions = {
            create: @json(auth()->user()->can('coupons.create')),
            update: @json(auth()->user()->can('coupons.update')),
            delete: @json(auth()->user()->can('coupons.delete')),
        };
        const csrfToken = @json(csrf_token());

        let couponRows = [];
        let couponPagination = {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        };
        const couponDatePickers = {};
        const couponSelects = ['#coupon_status', '#coupon_per_page', '#discount_type', '#status', '#eligible_product_ids', '#eligible_category_ids'];

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function money(value) {
            const amount = Number(value ?? 0);
            return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
        }

        function couponFilters(page = 1) {
            return {
                page,
                per_page: $('#coupon_per_page').val(),
            };
        }

        function routeFor(template, id) {
            return template.replace('__ID__', id);
        }

        function requestJson(url, options = {}) {
            return $.ajax({
                url,
                method: options.method || 'GET',
                data: options.body ? JSON.stringify(options.body) : null,
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                throw new Error(errors || 'Request failed.');
            });
        }

        function showFormErrors(message) {
            $('#coupon-form-errors').removeClass('d-none').text(message || 'Unable to save coupon.');
        }

        function clearFormErrors() {
            $('#coupon-form-errors').addClass('d-none').text('');
        }

        function selectedValues(selector) {
            return $(selector).val() || [];
        }

        function lifecycleBadge(status) {
            const colors = {
                active: 'success',
                inactive: 'secondary',
                scheduled: 'info',
                expired: 'warning',
            };

            return `<span class="badge text-bg-${colors[status] || 'secondary'}">${escapeHtml(status || 'unknown')}</span>`;
        }

        function discountLabel(coupon) {
            if (coupon.discount_type === 'percentage') {
                return `${money(coupon.discount_value)}%`;
            }

            if (coupon.discount_type === 'free_delivery') {
                return 'Free delivery';
            }

            return money(coupon.discount_value);
        }

        function scheduleLabel(coupon) {
            const starts = coupon.starts_at ? escapeHtml(coupon.starts_at) : 'Any time';
            const ends = coupon.ends_at ? escapeHtml(coupon.ends_at) : 'No end';

            return `${starts}<br><span class="text-secondary">${ends}</span>`;
        }

        function usageLabel(coupon) {
            const totalLimit = coupon.usage_limit ?? 'unlimited';
            const customerLimit = coupon.per_customer_usage_limit ?? 'unlimited';

            return `${escapeHtml(coupon.used_count)}/${escapeHtml(totalLimit)}<br><span class="text-secondary">Customer: ${escapeHtml(customerLimit)}</span>`;
        }

        function couponPerformance(couponId) {
            return (couponReport.coupons || []).find((row) => Number(row.id) === Number(couponId)) || null;
        }

        function renderCouponReport() {
            const summary = couponReport.summary || {};

            $('#coupon-total-count').text(summary.total_coupons || 0);
            $('#coupon-active-count').text(summary.active_coupons || 0);
            $('#coupon-redemption-count').text(summary.total_redemptions || 0);
            $('#coupon-discount-total').text(summary.total_discount || '0.00');

            const rows = couponReport.coupons || [];

            if (!rows.length) {
                $('#coupon-performance-table-body').html('<tr><td colspan="4" class="text-center py-4 text-secondary">No coupon performance rows yet.</td></tr>');
                return;
            }

            $('#coupon-performance-table-body').html(rows.map((coupon) => `
                <tr>
                    <td><strong>${escapeHtml(coupon.code)}</strong><br><span class="text-secondary">${escapeHtml(coupon.name)}</span></td>
                    <td>${lifecycleBadge(coupon.lifecycle_status)}</td>
                    <td>${escapeHtml(coupon.redemptions_count)}</td>
                    <td>${money(coupon.discount_total)}</td>
                </tr>
            `).join(''));
        }

        function renderCouponAudits() {
            if (!couponAudits.length) {
                $('#coupon-audit-table-body').html('<tr><td colspan="4" class="text-center py-4 text-secondary">No coupon audit events yet.</td></tr>');
                return;
            }

            $('#coupon-audit-table-body').html(couponAudits.map((audit) => `
                <tr>
                    <td>${escapeHtml(audit.event)}</td>
                    <td>${escapeHtml(audit.metadata?.code || 'N/A')}</td>
                    <td>${escapeHtml(audit.actor_id || 'System')}</td>
                    <td>${escapeHtml(audit.created_at || 'N/A')}</td>
                </tr>
            `).join(''));
        }

        function renderUsageDetail(coupon) {
            const performance = couponPerformance(coupon.id);
            const redemptionCount = performance ? performance.redemptions_count : 0;
            const discountTotal = performance ? performance.discount_total : '0.00';

            $('#coupon-usage-detail').html(`
                <dl class="row mb-0">
                    <dt class="col-sm-5">Code</dt><dd class="col-sm-7"><strong>${escapeHtml(coupon.code)}</strong></dd>
                    <dt class="col-sm-5">Lifecycle</dt><dd class="col-sm-7">${lifecycleBadge(coupon.lifecycle_status)}</dd>
                    <dt class="col-sm-5">Usage</dt><dd class="col-sm-7">${escapeHtml(coupon.used_count)} of ${escapeHtml(coupon.usage_limit ?? 'unlimited')}</dd>
                    <dt class="col-sm-5">Customer limit</dt><dd class="col-sm-7">${escapeHtml(coupon.per_customer_usage_limit ?? 'unlimited')}</dd>
                    <dt class="col-sm-5">Redemptions</dt><dd class="col-sm-7">${escapeHtml(redemptionCount)}</dd>
                    <dt class="col-sm-5">Discount total</dt><dd class="col-sm-7">${money(discountTotal)}</dd>
                    <dt class="col-sm-5">Products</dt><dd class="col-sm-7">${escapeHtml(commaList(coupon.eligible_product_ids) || 'All products')}</dd>
                    <dt class="col-sm-5">Categories</dt><dd class="col-sm-7">${escapeHtml(commaList(coupon.eligible_category_ids) || 'All categories')}</dd>
                </dl>
            `);
        }

        function actionButtons(coupon) {
            if (!couponPermissions.update && !couponPermissions.delete) {
                return '<span class="text-secondary">View only</span>';
            }

            const toggleLabel = coupon.status === 'active' ? 'Deactivate' : 'Activate';
            const toggleClass = coupon.status === 'active' ? 'outline-warning' : 'outline-success';
            const buttons = [];

            if (couponPermissions.update) {
                buttons.push(`<button type="button" class="btn btn-sm btn-outline-secondary view-coupon-usage" data-id="${coupon.id}">Usage</button>`);
                buttons.push(`<button type="button" class="btn btn-sm btn-outline-primary edit-coupon" data-id="${coupon.id}">Edit</button>`);
                buttons.push(`<button type="button" class="btn btn-sm btn-${toggleClass} toggle-coupon-status" data-id="${coupon.id}" data-status="${escapeHtml(coupon.status)}">${toggleLabel}</button>`);
            }

            if (couponPermissions.delete) {
                buttons.push(`<button type="button" class="btn btn-sm btn-outline-danger delete-coupon" data-id="${coupon.id}">Delete</button>`);
            }

            return `<div class="btn-group">${buttons.join('')}</div>`;
        }

        function filteredCoupons() {
            const search = $('#coupon_search').val().trim().toLowerCase();
            const status = $('#coupon_status').val();

            return couponRows.filter((coupon) => {
                const matchesSearch = !search
                    || String(coupon.code || '').toLowerCase().includes(search)
                    || String(coupon.name || '').toLowerCase().includes(search);
                const matchesStatus = !status || coupon.lifecycle_status === status || coupon.status === status;

                return matchesSearch && matchesStatus;
            });
        }

        function renderCoupons() {
            const rows = filteredCoupons();

          //  $('#coupon-list-summary').text(`${couponPagination.total} coupons loaded, ${rows.length} visible after filters.`);
            $('#coupon-pagination-summary').text(`Page ${couponPagination.current_page} of ${couponPagination.last_page}`);
            $('#previous-coupon-page').prop('disabled', couponPagination.current_page <= 1);
            $('#next-coupon-page').prop('disabled', couponPagination.current_page >= couponPagination.last_page);

            if (!rows.length) {
                const message = couponRows.length ? 'No coupons match these filters.' : 'No coupons created yet.';
                $('#coupon-table-body').html(`<tr><td colspan="8" class="text-center py-4 text-secondary">${message}</td></tr>`);
                return;
            }

            $('#coupon-table-body').html(rows.map((coupon) => `
                <tr>
                    <td><strong>${escapeHtml(coupon.code)}</strong></td>
                    <td>${escapeHtml(coupon.name)}</td>
                    <td>${escapeHtml(discountLabel(coupon))}<br><span class="text-secondary">${escapeHtml(coupon.discount_type)}</span></td>
                    <td>${money(coupon.minimum_order_value)}</td>
                    <td>${lifecycleBadge(coupon.lifecycle_status)}</td>
                    <td>${scheduleLabel(coupon)}</td>
                    <td>${usageLabel(coupon)}</td>
                    <td class="text-end">${actionButtons(coupon)}</td>
                </tr>
            `).join(''));
        }

        function loadCoupons(page = 1) {
            $('#coupon-table-body').html('<tr><td colspan="8" class="text-center py-4 text-secondary">Loading coupons...</td></tr>');

            return $.ajax({
                url: couponRoutes.data,
                method: 'GET',
                dataType: 'json',
                data: couponFilters(page),
                headers: { 'Accept': 'application/json' },
            }).then((body) => {
                couponRows = body.data || [];
                couponPagination = body.meta?.pagination || couponPagination;
                renderCoupons();
            }).catch(() => {
                $('#coupon-table-body').html('<tr><td colspan="8" class="text-center py-4 text-danger">Unable to load coupons.</td></tr>');
                adminToast('Unable to load coupons.', 'danger');
            });
        }

        function commaList(value) {
            return Array.isArray(value) ? value.join(',') : '';
        }

        function selectOptions(selector, values) {
            const selected = (Array.isArray(values) ? values : []).map((value) => String(value));

            $(`${selector} option`).each(function () {
                $(this).prop('selected', selected.includes($(this).val()));
            });

            $(selector).trigger('change.select2');
        }

        function initCouponSelect2() {
            if (typeof $.fn.select2 !== 'function') {
                return;
            }

            $('.js-select2').each(function () {
                const $select = $(this);
                const isMultiple = $select.prop('multiple');

                $select.select2({
                    width: '100%',
                    placeholder: $select.data('placeholder') || '',
                    allowClear: !isMultiple,
                    closeOnSelect: !isMultiple,
                });
            });
        }

        /**
         * HTML datetime-local only accepts yyyy-MM-ddThh:mm. API may return ISO (Z), SQL spacing, or microseconds.
         * Naive truncation breaks values like "2026-05-05 09:07:08" → "2026-05-05 09:0", which triggers
         * native "Please enter a valid date and time" and blocks submit.
         */
        function normalizeDateTime(value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            const raw = String(value).trim();

            if (!raw) {
                return '';
            }

            const patched = raw.includes(' ')
                ? raw.replace(' ', 'T')
                : raw;

            const d = new Date(patched);

            if (Number.isNaN(d.getTime())) {
                return '';
            }

            const pad = (n) => String(n).padStart(2, '0');

            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }

        function toServerDateTime(localValue) {
            if (!localValue) {
                return null;
            }

            const s = String(localValue).trim();

            if (!s) {
                return null;
            }

            if (!s.includes('T')) {
                return s;
            }

            return s.replace('T', ' ') + (s.length === 16 ? ':00' : '');
        }

        function pickerValue(selector) {
            const picker = couponDatePickers[selector];

            if (picker?.selectedDates?.length) {
                return picker.formatDate(picker.selectedDates[0], 'Y-m-d H:i:S');
            }

            return toServerDateTime($(selector).val());
        }

        function setPickerValue(selector, value) {
            const picker = couponDatePickers[selector];

            if (!picker) {
                $(selector).val(normalizeDateTime(value));
                return;
            }

            const normalized = normalizeDateTime(value);

            if (!normalized) {
                picker.clear();
                return;
            }

            picker.setDate(normalized, true);
        }

        function initCouponDatePickers() {
            if (typeof flatpickr !== 'function') {
                return;
            }

            ['#starts_at', '#ends_at'].forEach((selector) => {
                couponDatePickers[selector] = flatpickr(selector, {
                    enableTime: true,
                    allowInput: true,
                    minuteIncrement: 1,
                    dateFormat: 'Y-m-d H:i:S',
                    altInput: true,
                    altFormat: 'M j, Y h:i K',
                });
            });
        }

        function couponPayload() {
            return {
                name: $('#name').val(),
                code: $('#code').val().toUpperCase(),
                discount_type: $('#discount_type').val(),
                discount_value: $('#discount_value').val() || null,
                minimum_order_value: $('#minimum_order_value').val() || null,
                status: $('#status').val(),
                starts_at: pickerValue('#starts_at'),
                ends_at: pickerValue('#ends_at'),
                eligible_product_ids: selectedValues('#eligible_product_ids'),
                eligible_category_ids: selectedValues('#eligible_category_ids'),
                usage_limit: $('#usage_limit').val() || null,
                per_customer_usage_limit: $('#per_customer_usage_limit').val() || null,
            };
        }

        function resetCouponForm() {
            $('#coupon-form')[0].reset();
            clearFormErrors();
            $('#coupon_id').val('');
            $('#coupon-form-title').text('Create Coupon');
            $('#discount_type').val('fixed');
            $('#discount_value').val('0').prop('disabled', false);
            $('#status').val('inactive');
            $('#discount_type').trigger('change.select2');
            $('#status').trigger('change.select2');
            setPickerValue('#starts_at', null);
            setPickerValue('#ends_at', null);
            selectOptions('#eligible_product_ids', []);
            selectOptions('#eligible_category_ids', []);
        }

        function setCouponForm(coupon) {
            clearFormErrors();
            $('#coupon_id').val(coupon.id);
            $('#coupon-form-title').text(`Edit Coupon #${coupon.id}`);
            $('#name').val(coupon.name);
            $('#code').val(coupon.code);
            $('#discount_type').val(coupon.discount_type);
            $('#discount_value').val(money(coupon.discount_value));
            $('#minimum_order_value').val(coupon.minimum_order_value ?? '');
            $('#status').val(coupon.status);
            $('#discount_type').trigger('change.select2');
            $('#status').trigger('change.select2');
            setPickerValue('#starts_at', coupon.starts_at);
            setPickerValue('#ends_at', coupon.ends_at);
            $('#usage_limit').val(coupon.usage_limit ?? '');
            $('#per_customer_usage_limit').val(coupon.per_customer_usage_limit ?? '');
            selectOptions('#eligible_product_ids', coupon.eligible_product_ids);
            selectOptions('#eligible_category_ids', coupon.eligible_category_ids);
            toggleDiscountValue();
            renderUsageDetail(coupon);
        }

        function toggleDiscountValue() {
            const isFreeDelivery = $('#discount_type').val() === 'free_delivery';
            $('#discount_value').prop('disabled', isFreeDelivery);

            if (isFreeDelivery) {
                $('#discount_value').val('0');
            }
        }

        function saveCoupon() {
            clearFormErrors();
            const id = $('#coupon_id').val();
            const url = id ? routeFor(couponRoutes.update, id) : couponRoutes.store;
            const method = id ? 'PUT' : 'POST';

            if (!id && !couponPermissions.create) {
                showFormErrors('You do not have permission to create coupons.');
                return;
            }

            if (id && !couponPermissions.update) {
                showFormErrors('You do not have permission to update coupons.');
                return;
            }

            return requestJson(url, {
                method,
                body: couponPayload(),
            }).then((body) => {
                adminToast(body.message || 'Coupon saved successfully.');
                resetCouponForm();
                loadCoupons(couponPagination.current_page);
            }).catch((error) => {
                showFormErrors(error.message || 'Unable to save coupon.');
                adminToast(error.message || 'Unable to save coupon.', 'danger');
            });
        }

        function editCoupon(id) {
            return requestJson(routeFor(couponRoutes.show, id))
                .then((body) => setCouponForm(body.data))
                .catch((error) => adminToast(error.message || 'Unable to load coupon.', 'danger'));
        }

        function viewCouponUsage(id) {
            const coupon = couponRows.find((row) => Number(row.id) === Number(id));

            if (coupon) {
                renderUsageDetail(coupon);
                return;
            }

            return requestJson(routeFor(couponRoutes.show, id))
                .then((body) => renderUsageDetail(body.data))
                .catch((error) => adminToast(error.message || 'Unable to load coupon usage.', 'danger'));
        }

        function toggleCouponStatus(id, status) {
            const route = status === 'active' ? couponRoutes.deactivate : couponRoutes.activate;

            return requestJson(routeFor(route, id), {
                method: 'PATCH',
            }).then((body) => {
                adminToast(body.message || 'Coupon status updated.');
                loadCoupons(couponPagination.current_page);
            }).catch((error) => {
                adminToast(error.message || 'Unable to update coupon status.', 'danger');
            });
        }

        function deleteCoupon(id) {
            if (!confirm('Delete this coupon?')) {
                return;
            }

            return requestJson(routeFor(couponRoutes.destroy, id), {
                method: 'DELETE',
            }).then((body) => {
                adminToast(body.message || 'Coupon deleted successfully.');
                resetCouponForm();
                loadCoupons(couponPagination.current_page);
            }).catch((error) => {
                adminToast(error.message || 'Unable to delete coupon.', 'danger');
            });
        }

        $('#coupon-filter-form').on('submit', function (event) {
            event.preventDefault();
            renderCoupons();
        });

        $('#coupon_search, #coupon_status').on('input change', renderCoupons);

        $('#coupon_per_page').on('change', function () {
            loadCoupons(1);
        });

        $('#reset-coupon-filters').on('click', function () {
            $('#coupon-filter-form')[0].reset();
            $('#coupon_per_page').val('15');
            $('#coupon_status').val('').trigger('change.select2');
            $('#coupon_per_page').trigger('change.select2');
            loadCoupons(1);
        });

        $('#refresh-coupons').on('click', function () {
            loadCoupons(couponPagination.current_page);
        });

        $('#new-coupon, #reset-coupon-form').on('click', resetCouponForm);

        $('#discount_type').on('change', toggleDiscountValue);

        $('#code').on('input', function () {
            $(this).val($(this).val().toUpperCase());
        });

        $('#coupon-form').on('submit', function (event) {
            event.preventDefault();
            saveCoupon();
        });

        $('#coupon-table-body').on('click', '.edit-coupon', function () {
            editCoupon($(this).data('id'));
        });

        $('#coupon-table-body').on('click', '.view-coupon-usage', function () {
            viewCouponUsage($(this).data('id'));
        });

        $('#coupon-table-body').on('click', '.toggle-coupon-status', function () {
            toggleCouponStatus($(this).data('id'), $(this).data('status'));
        });

        $('#coupon-table-body').on('click', '.delete-coupon', function () {
            deleteCoupon($(this).data('id'));
        });

        $('#previous-coupon-page').on('click', function () {
            if (couponPagination.current_page > 1) {
                loadCoupons(couponPagination.current_page - 1);
            }
        });

        $('#next-coupon-page').on('click', function () {
            if (couponPagination.current_page < couponPagination.last_page) {
                loadCoupons(couponPagination.current_page + 1);
            }
        });

        initCouponSelect2();
        initCouponDatePickers();
        resetCouponForm();
        renderCouponReport();
        renderCouponAudits();
        loadCoupons();
    </script>
@endpush
