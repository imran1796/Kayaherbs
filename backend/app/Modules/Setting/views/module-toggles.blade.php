@extends('admin.layouts.app')

@section('title', 'Module Toggles')
@section('page_title', 'Module Toggles')
@section('page_subtitle', 'Enable or disable platform modules without deployment changes.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Module Toggles</li>
@endsection

@section('content')
    <form id="module-toggle-form" method="post" action="{{ route('admin.settings.module-toggles.update') }}">
        @csrf
        @method('put')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Basic Module Toggles</h3>
            </div>
            <div class="card-body">
                @php($labels = [
                    'catalog' => 'Catalog',
                    'inventory' => 'Inventory',
                    'checkout' => 'Checkout',
                    'coupons' => 'Coupons',
                    'reviews' => 'Reviews',
                    'blog' => 'Blog',
                    'category' => 'Category',
                ])

                <div class="row g-3">
                    @foreach ($labels as $key => $label)
                        @php($checked = (bool) old($key, $toggles[$key] ?? false))
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input class="form-check-input @error($key) is-invalid @enderror"
                                       type="checkbox"
                                       role="switch"
                                       id="{{ $key }}"
                                       name="{{ $key }}"
                                       value="1"
                                    @checked($checked)>
                                <label class="form-check-label fw-semibold" for="{{ $key }}">{{ $label }}</label>
                                @error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Save toggles</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function moduleTogglePayload() {
            const payload = {};

            $('#module-toggle-form input[type="checkbox"]').each(function () {
                payload[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
            });

            return payload;
        }

        function settingErrors(xhr) {
            const body = xhr.responseJSON || {};
            return body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Request failed.');
        }

        $('#module-toggle-form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                method: 'PUT',
                data: JSON.stringify(moduleTogglePayload()),
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
            })
                .done((body) => adminToast(body.message || 'Module toggles updated.'))
                .fail((xhr) => adminToast(settingErrors(xhr), 'error'));
        });
    </script>
@endpush
