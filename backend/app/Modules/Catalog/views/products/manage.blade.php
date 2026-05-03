@extends('admin.layouts.app')

@section('title', 'Products')
@section('page_title', 'Products')
@section('page_subtitle', 'Manage products, variants, images, and publishing state.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Products</li>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h3 class="card-title">Products</h3>
                            <p class="text-secondary mb-0 mt-1">Loaded and managed with AJAX.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-products">Refresh</button>
                    </div>
                </div>
                <div class="card-body border-top">
                    <form id="product-filter-form" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="product_search" class="form-label">Search</label>
                            <input id="product_search" class="form-control" placeholder="Name or slug">
                        </div>
                        <div class="col-md-3">
                            <label for="product_status_filter" class="form-label">Status</label>
                            <select id="product_status_filter" class="form-select">
                                <option value="">All statuses</option>
                                <option value="draft">Draft</option>
                                <option value="unpublished">Unpublished</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="product_category_filter" class="form-label">Category</label>
                            <select id="product_category_filter" class="form-select">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="clear-product-filters">Clear</button>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Variants</th>
                                    <th>Images</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="product-table-body">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">Loading products...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <form id="product-form" class="card">
                <div class="card-header">
                    <h3 class="card-title" id="product-form-title">Create Product</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" id="product_id">

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input id="slug" name="slug" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3" class="form-control"></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="unpublished">Unpublished</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="category_ids" class="form-label">Categories</label>
                            <select id="category_ids" name="category_ids[]" class="form-select" multiple>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h4 class="h6 mb-0">Variants</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-variant">Add variant</button>
                    </div>

                    <div id="variants-container">
                        <div class="border rounded p-3 mb-3 product-variant-row">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <span class="fw-semibold">Variant #1</span>
                                <div class="form-check mb-0">
                                    <input class="form-check-input variant-default" type="radio" name="default_variant" checked>
                                    <label class="form-check-label">Default</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Variant name</label>
                                <input class="form-control variant-name" value="Default" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">SKU</label>
                                    <input class="form-control variant-sku" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Price</label>
                                    <input type="number" min="0" step="0.01" class="form-control variant-price" required>
                                </div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Compare price</label>
                                    <input type="number" min="0" step="0.01" class="form-control variant-compare-price">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select variant-status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-variant" disabled>Remove</button>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="primary_image" class="form-label">Upload primary image</label>
                        <input id="primary_image" type="file" accept="image/*" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="image_path" class="form-label">Primary image path</label>
                        <input id="image_path" class="form-control" placeholder="/storage/products/example.jpg">
                    </div>

                    <div>
                        <label for="image_alt_text" class="form-label">Image alt text</label>
                        <input id="image_alt_text" class="form-control">
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="reset-product-form">Reset</button>
                    <button type="submit" class="btn btn-primary">Save product</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const productRoutes = {
            data: @json(route('admin.products.data')),
            store: @json(route('admin.products.store')),
            show: @json(route('admin.products.show', ['id' => '__ID__'])),
            update: @json(route('admin.products.update', ['id' => '__ID__'])),
            publish: @json(route('admin.products.publish', ['id' => '__ID__'])),
            unpublish: @json(route('admin.products.unpublish', ['id' => '__ID__'])),
        };
        const csrfToken = @json(csrf_token());

        function routeFor(template, id) {
            return template.replace('__ID__', id);
        }

        function showToast(message, type = 'success') {
            adminToast(message, type);
        }

        function requestJson(url, options = {}) {
            return $.ajax({
                url,
                method: options.method || 'GET',
                data: options.body || null,
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    ...(options.headers || {}),
                },
            }).catch((xhr) => {
                const body = xhr.responseJSON || {};
                const errors = body.errors ? Object.values(body.errors).flat().join(' ') : body.message;
                throw new Error(errors || 'Request failed.');
            });
        }

        function requestForm(url, formData) {
            return $.ajax({
                url,
                method: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
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

        function appendVariant(formData, index, variant) {
            Object.entries(variant).forEach(([key, value]) => {
                formData.append(`variants[${index}][${key}]`, value);
            });
        }

        function appendImage(formData, index, image) {
            Object.entries(image).forEach(([key, value]) => {
                formData.append(`images[${index}][${key}]`, value);
            });
        }

        function escapeAttribute(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('"', '&quot;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;');
        }

        function isDefaultVariant(value) {
            return value === true || value === 1 || value === '1' || value === 'true';
        }

        function variantRowHtml(variant = {}, index = 0, rowCount = 1) {
            const isDefault = isDefaultVariant(variant.is_default);
            const status = variant.status || 'active';

            return `
                <div class="border rounded p-3 mb-3 product-variant-row">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <span class="fw-semibold">Variant #${index + 1}</span>
                        <div class="form-check mb-0">
                            <input class="form-check-input variant-default" type="radio" name="default_variant" ${isDefault ? 'checked' : ''}>
                            <label class="form-check-label">Default</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Variant name</label>
                        <input class="form-control variant-name" value="${escapeAttribute(variant.name || 'Default')}" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input class="form-control variant-sku" value="${escapeAttribute(variant.sku)}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input type="number" min="0" step="0.01" class="form-control variant-price" value="${escapeAttribute(variant.price)}" required>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Compare price</label>
                            <input type="number" min="0" step="0.01" class="form-control variant-compare-price" value="${escapeAttribute(variant.compare_at_price)}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select variant-status">
                                <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-variant" ${rowCount === 1 ? 'disabled' : ''}>Remove</button>
                    </div>
                </div>
            `;
        }

        function renderVariantRows(variants = []) {
            const rows = variants.length ? variants : [{ name: 'Default', status: 'active', is_default: true }];
            const hasDefault = rows.some((variant) => isDefaultVariant(variant.is_default));

            $('#variants-container').html(rows.map((variant, index) => variantRowHtml({
                ...variant,
                is_default: hasDefault ? isDefaultVariant(variant.is_default) : index === 0,
            }, index, rows.length)).join(''));
        }

        function variantRows() {
            return $('#variants-container .product-variant-row').toArray();
        }

        function collectVariants() {
            return variantRows().map((row, index) => ({
                name: $(row).find('.variant-name').val() || 'Default',
                sku: $(row).find('.variant-sku').val(),
                price: $(row).find('.variant-price').val(),
                compare_at_price: $(row).find('.variant-compare-price').val(),
                sort_order: String(index),
                is_default: $(row).find('.variant-default').is(':checked') ? '1' : '0',
                status: $(row).find('.variant-status').val(),
            }));
        }

        function refreshVariantLabels() {
            const rows = variantRows();

            rows.forEach((row, index) => {
                $(row).find('.fw-semibold').text(`Variant #${index + 1}`);
                $(row).find('.remove-variant').prop('disabled', rows.length === 1);
            });

            if (!rows.some((row) => $(row).find('.variant-default').is(':checked'))) {
                $(rows[0]).find('.variant-default').trigger('click');
            }
        }

        function productFormData() {
            const imagePath = $('#image_path').val().trim();
            const imageFile = $('#primary_image')[0].files[0];
            const formData = new FormData();

            formData.append('name', $('#name').val());
            formData.append('slug', $('#slug').val());
            formData.append('description', $('#description').val());
            formData.append('status', $('#status').val());

            $('#category_ids option:selected').each(function () {
                formData.append('category_ids[]', $(this).val());
            });

            collectVariants().forEach((variant, index) => {
                appendVariant(formData, index, variant);
            });

            if (imageFile) {
                formData.append('primary_image', imageFile);
                formData.append('image_alt_text', $('#image_alt_text').val());
            }

            if (!imageFile && imagePath) {
                appendImage(formData, 0, {
                    path: imagePath,
                    alt_text: $('#image_alt_text').val(),
                    is_primary: '1',
                });
            }

            return formData;
        }

        function resetForm() {
            $('#product-form')[0].reset();
            $('#primary_image').val('');
            $('#product_id').val('');
            $('#product-form-title').text('Create Product');
            renderVariantRows();
        }

        function setForm(product) {
            $('#product_id').val(product.id);
            $('#product-form-title').text(`Edit Product #${product.id}`);
            $('#name').val(product.name || '');
            $('#slug').val(product.slug || '');
            $('#description').val(product.description || '');
            $('#status').val(product.status || 'draft');
            $('#category_ids option').each(function () {
                $(this).prop('selected', (product.categories || []).some((category) => String(category.id) === $(this).val()));
            });
            renderVariantRows(product.variants || []);
            const image = (product.images || [])[0] || {};
            $('#primary_image').val('');
            $('#image_path').val(image.path || '');
            $('#image_alt_text').val(image.alt_text || '');
        }

        function renderProducts(products) {
            if (!products.length) {
                $('#product-table-body').html('<tr><td colspan="5" class="text-center py-4 text-secondary">No products match these filters.</td></tr>');
                return;
            }

            $('#product-table-body').html(products.map((product) => `
                <tr>
                    <td class="fw-semibold">${product.name}</td>
                    <td><span class="badge text-bg-${product.status === 'published' ? 'success' : 'secondary'}">${product.status}</span></td>
                    <td>${product.variants.length}</td>
                    <td>${product.images.length}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${product.id}">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-success" data-action="publish" data-id="${product.id}">Publish</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-action="unpublish" data-id="${product.id}">Unpublish</button>
                    </td>
                </tr>
            `).join(''));
        }

        function loadProducts() {
            const params = new URLSearchParams();
            const search = $('#product_search').val().trim();
            const status = $('#product_status_filter').val();
            const categoryId = $('#product_category_filter').val();

            if (search) {
                params.set('search', search);
            }

            if (status) {
                params.set('status', status);
            }

            if (categoryId) {
                params.set('category_id', categoryId);
            }

            const url = params.toString() ? `${productRoutes.data}?${params.toString()}` : productRoutes.data;

            return requestJson(url).then((body) => {
                renderProducts(body.data);
            });
        }

        $('#product-table-body').on('click', 'button[data-action]', function () {
            const id = $(this).data('id');
            const action = $(this).data('action');

            if (action === 'edit') {
                requestJson(routeFor(productRoutes.show, id))
                    .then((body) => {
                    setForm(body.data);
                    })
                    .catch((error) => showToast(error.message, 'danger'));
                return;
            }

            requestJson(routeFor(productRoutes[action], id), { method: 'POST' })
                .then((body) => {
                showToast(body.message || `Product ${action}ed successfully.`);
                    return loadProducts();
                })
                .catch((error) => showToast(error.message, 'danger'));
        });

        $('#product-form').on('submit', function (event) {
            event.preventDefault();

            const id = $('#product_id').val();
            const url = id ? routeFor(productRoutes.update, id) : productRoutes.store;
            const formData = productFormData();

            if (id) {
                formData.append('_method', 'PUT');
            }

            requestForm(url, formData)
                .then((body) => {
                resetForm();
                showToast(body.message || 'Product saved successfully.');
                    return loadProducts();
                })
                .catch((error) => showToast(error.message, 'danger'));
        });

        $('#refresh-products').on('click', loadProducts);
        $('#product-filter-form').on('submit', function (event) {
            event.preventDefault();
            loadProducts().catch((error) => showToast(error.message, 'danger'));
        });
        $('#clear-product-filters').on('click', function () {
            $('#product_search').val('');
            $('#product_status_filter').val('');
            $('#product_category_filter').val('');
            loadProducts().catch((error) => showToast(error.message, 'danger'));
        });
        $('#reset-product-form').on('click', resetForm);
        $('#add-variant').on('click', () => {
            renderVariantRows([...collectVariants(), { name: 'Default', status: 'active' }]);
        });
        $('#variants-container').on('click', '.remove-variant', function () {
            $(this).closest('.product-variant-row').remove();
            refreshVariantLabels();
        });
        renderVariantRows();
        loadProducts().catch((error) => showToast(error.message, 'danger'));
    </script>
@endpush
