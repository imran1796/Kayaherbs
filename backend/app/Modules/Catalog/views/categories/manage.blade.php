@extends('admin.layouts.app')

@section('title', 'Categories')
@section('page_title', 'Categories')
@section('page_subtitle', 'Manage catalog categories with AJAX.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Categories</li>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title">Categories</h3>
                            <p class="text-secondary mb-0 mt-1">Loaded and managed with AJAX.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-categories">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Parent</th>
                                    <th>Status</th>
                                    <th>Sort</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="category-table-body">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-secondary">Loading categories...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <form id="category-form" class="card">
                <div class="card-header">
                    <h3 class="card-title" id="category-form-title">Create Category</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" id="category_id">

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input id="slug" class="form-control">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="parent_id" class="form-label">Parent</label>
                            <select id="parent_id" class="form-select">
                                <option value="">None</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_order" class="form-label">Sort</label>
                            <input id="sort_order" type="number" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="image_path" class="form-label">Image path</label>
                        <input id="image_path" class="form-control" placeholder="/storage/categories/herbs.jpg">
                    </div>

                    <div class="mt-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="reset-category-form">Reset</button>
                    <button type="submit" class="btn btn-primary">Save category</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const categoryRoutes = {
            data: @json(route('admin.categories.data')),
            store: @json(route('admin.categories.store')),
            show: @json(route('admin.categories.show', ['id' => '__ID__'])),
            update: @json(route('admin.categories.update', ['id' => '__ID__'])),
            destroy: @json(route('admin.categories.destroy', ['id' => '__ID__'])),
        };
        const categoryPermissions = {
            update: @json(auth()->user()->can('categories.update')),
            delete: @json(auth()->user()->can('categories.delete')),
        };
        const csrfToken = @json(csrf_token());

        let categories = [];

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

        function categoryPayload() {
            return {
                parent_id: $('#parent_id').val() || null,
                name: $('#name').val(),
                slug: $('#slug').val(),
                description: $('#description').val(),
                image_path: $('#image_path').val(),
                sort_order: $('#sort_order').val() || 0,
                status: $('#status').val(),
            };
        }

        function resetForm() {
            $('#category-form')[0].reset();
            $('#category_id').val('');
            $('#category-form-title').text('Create Category');
            $('#sort_order').val(0);
            renderParentOptions();
        }

        function setForm(category) {
            $('#category_id').val(category.id);
            $('#category-form-title').text(`Edit Category #${category.id}`);
            $('#name').val(category.name || '');
            $('#slug').val(category.slug || '');
            $('#description').val(category.description || '');
            $('#image_path').val(category.image_path || '');
            $('#sort_order').val(category.sort_order || 0);
            $('#status').val(category.status || 'active');
            renderParentOptions(category.id);
            $('#parent_id').val(category.parent_id || '');
        }

        function renderParentOptions(currentId = null) {
            const selected = $('#parent_id').val();
            const options = categories
                .filter((category) => String(category.id) !== String(currentId))
                .map((category) => `<option value="${category.id}">${escapeHtml(category.name)}</option>`)
                .join('');

            $('#parent_id').html(`<option value="">None</option>${options}`).val(selected);
        }

        function renderCategories() {
            if (!categories.length) {
                $('#category-table-body').html('<tr><td colspan="6" class="text-center py-4 text-secondary">No categories found yet.</td></tr>');
                renderParentOptions();
                return;
            }

            $('#category-table-body').html(categories.map((category) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(category.name)}</td>
                    <td>${escapeHtml(category.slug)}</td>
                    <td>${escapeHtml(category.parent?.name || 'Root')}</td>
                    <td><span class="badge text-bg-${category.status === 'active' ? 'success' : 'secondary'}">${escapeHtml(category.status)}</span></td>
                    <td>${category.sort_order}</td>
                    <td class="text-end">
                        ${categoryPermissions.update ? `<button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${category.id}">Edit</button>` : ''}
                        ${categoryPermissions.delete ? `<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${category.id}">Delete</button>` : ''}
                    </td>
                </tr>
            `).join(''));
            renderParentOptions($('#category_id').val() || null);
        }

        function loadCategories() {
            return requestJson(`${categoryRoutes.data}?per_page=100`).then((body) => {
                categories = body.data;
                renderCategories();
            });
        }

        $('#category-table-body').on('click', 'button[data-action]', function () {
            const id = $(this).data('id');
            const action = $(this).data('action');

            if (action === 'edit') {
                requestJson(routeFor(categoryRoutes.show, id))
                    .then((body) => {
                    setForm(body.data);
                    })
                    .catch((error) => showToast(error.message, 'danger'));
                return;
            }

            if (!confirm('Delete this category?')) {
                return;
            }

            requestJson(routeFor(categoryRoutes.destroy, id), { method: 'DELETE' })
                .then(() => {
                resetForm();
                showToast('Category deleted successfully.');
                    return loadCategories();
                })
                .catch((error) => showToast(error.message, 'danger'));
        });

        $('#category-form').on('submit', function (event) {
            event.preventDefault();

            const id = $('#category_id').val();
            const url = id ? routeFor(categoryRoutes.update, id) : categoryRoutes.store;
            const method = id ? 'PUT' : 'POST';

            requestJson(url, {
                    method,
                    body: JSON.stringify(categoryPayload()),
                })
                .then(() => {
                resetForm();
                showToast('Category saved successfully.');
                    return loadCategories();
                })
                .catch((error) => showToast(error.message, 'danger'));
        });

        $('#refresh-categories').on('click', loadCategories);
        $('#reset-category-form').on('click', resetForm);
        loadCategories().catch((error) => showToast(error.message, 'danger'));
    </script>
@endpush
