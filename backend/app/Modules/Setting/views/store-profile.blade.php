@extends('admin.layouts.app')

@section('title', 'Store Profile')
@section('page_title', 'Store Profile')
@section('page_subtitle', 'Manage the public identity, support, address, and localization defaults for the store.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Store Profile</li>
@endsection

@section('content')
    <form id="store-profile-form" method="post" action="{{ route('admin.settings.store-profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('put')

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Identity</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Store name</label>
                                <input id="name" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name', $profile['name']) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="legal_name" class="form-label">Legal name</label>
                                <input id="legal_name" name="legal_name" class="form-control form-control-sm @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', $profile['legal_name']) }}">
                                @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="support_email" class="form-label">Support email</label>
                                <input id="support_email" type="email" name="support_email" class="form-control form-control-sm @error('support_email') is-invalid @enderror" value="{{ old('support_email', $profile['support_email']) }}" required>
                                @error('support_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="support_phone" class="form-label">Support phone</label>
                                <input id="support_phone" name="support_phone" class="form-control form-control-sm @error('support_phone') is-invalid @enderror" value="{{ old('support_phone', $profile['support_phone']) }}">
                                @error('support_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="website_url" class="form-label">Website URL</label>
                                <input id="website_url" type="url" name="website_url" class="form-control form-control-sm @error('website_url') is-invalid @enderror" value="{{ old('website_url', $profile['website_url']) }}">
                                @error('website_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Address</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="address_line_1" class="form-label">Address line 1</label>
                                <input id="address_line_1" name="address_line_1" class="form-control form-control-sm @error('address_line_1') is-invalid @enderror" value="{{ old('address_line_1', $profile['address_line_1']) }}">
                                @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="address_line_2" class="form-label">Address line 2</label>
                                <input id="address_line_2" name="address_line_2" class="form-control form-control-sm @error('address_line_2') is-invalid @enderror" value="{{ old('address_line_2', $profile['address_line_2']) }}">
                                @error('address_line_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input id="city" name="city" class="form-control form-control-sm @error('city') is-invalid @enderror" value="{{ old('city', $profile['city']) }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="state" class="form-label">State/Division</label>
                                <input id="state" name="state" class="form-control form-control-sm @error('state') is-invalid @enderror" value="{{ old('state', $profile['state']) }}">
                                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="postal_code" class="form-label">Postal code</label>
                                <input id="postal_code" name="postal_code" class="form-control form-control-sm @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $profile['postal_code']) }}">
                                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input id="country" name="country" class="form-control form-control-sm text-uppercase @error('country') is-invalid @enderror" value="{{ old('country', $profile['country']) }}" maxlength="2" required>
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Localization</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input id="currency" name="currency" class="form-control form-control-sm text-uppercase @error('currency') is-invalid @enderror" value="{{ old('currency', $profile['currency']) }}" maxlength="3" required>
                            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <input id="timezone" name="timezone" class="form-control form-control-sm @error('timezone') is-invalid @enderror" value="{{ old('timezone', $profile['timezone']) }}" required>
                            @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="locale" class="form-label">Locale</label>
                            <input id="locale" name="locale" class="form-control form-control-sm @error('locale') is-invalid @enderror" value="{{ old('locale', $profile['locale']) }}" required>
                            @error('locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Save profile</button>
                    </div>
                </div>

                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Policy Pages</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="privacy_policy_title" class="form-label">Privacy policy title</label>
                            <input id="privacy_policy_title" name="privacy_policy_title" class="form-control form-control-sm @error('privacy_policy_title') is-invalid @enderror" value="{{ old('privacy_policy_title', $profile['privacy_policy_title'] ?? 'Privacy Policy') }}">
                            @error('privacy_policy_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="privacy_policy_content" class="form-label">Privacy policy content</label>
                            <textarea id="privacy_policy_content" name="privacy_policy_content" rows="5" class="form-control form-control-sm @error('privacy_policy_content') is-invalid @enderror">{{ old('privacy_policy_content', $profile['privacy_policy_content'] ?? '') }}</textarea>
                            @error('privacy_policy_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="terms_conditions_title" class="form-label">Terms & conditions title</label>
                            <input id="terms_conditions_title" name="terms_conditions_title" class="form-control form-control-sm @error('terms_conditions_title') is-invalid @enderror" value="{{ old('terms_conditions_title', $profile['terms_conditions_title'] ?? 'Terms & Conditions') }}">
                            @error('terms_conditions_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="terms_conditions_content" class="form-label">Terms & conditions content</label>
                            <textarea id="terms_conditions_content" name="terms_conditions_content" rows="5" class="form-control form-control-sm @error('terms_conditions_content') is-invalid @enderror">{{ old('terms_conditions_content', $profile['terms_conditions_content'] ?? '') }}</textarea>
                            @error('terms_conditions_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="refund_policy_title" class="form-label">Refund policy title</label>
                            <input id="refund_policy_title" name="refund_policy_title" class="form-control form-control-sm @error('refund_policy_title') is-invalid @enderror" value="{{ old('refund_policy_title', $profile['refund_policy_title'] ?? 'Refund Policy') }}">
                            @error('refund_policy_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="refund_policy_content" class="form-label">Refund policy content</label>
                            <textarea id="refund_policy_content" name="refund_policy_content" rows="5" class="form-control form-control-sm @error('refund_policy_content') is-invalid @enderror">{{ old('refund_policy_content', $profile['refund_policy_content'] ?? '') }}</textarea>
                            @error('refund_policy_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="shipping_policy_title" class="form-label">Shipping policy title</label>
                            <input id="shipping_policy_title" name="shipping_policy_title" class="form-control form-control-sm @error('shipping_policy_title') is-invalid @enderror" value="{{ old('shipping_policy_title', $profile['shipping_policy_title'] ?? 'Shipping Policy') }}">
                            @error('shipping_policy_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="shipping_policy_content" class="form-label">Shipping policy content</label>
                            <textarea id="shipping_policy_content" name="shipping_policy_content" rows="5" class="form-control form-control-sm @error('shipping_policy_content') is-invalid @enderror">{{ old('shipping_policy_content', $profile['shipping_policy_content'] ?? '') }}</textarea>
                            @error('shipping_policy_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Branding</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="primary_color" class="form-label">Primary color</label>
                            <div class="input-group input-group-sm">
                                <input id="primary_color_picker" type="color" class="form-control form-control-color @error('primary_color') is-invalid @enderror" value="{{ old('primary_color', $profile['primary_color'] ?? '#0B5FFF') }}">
                                <input id="primary_color" name="primary_color" class="form-control form-control-sm @error('primary_color') is-invalid @enderror" value="{{ old('primary_color', $profile['primary_color'] ?? '#0B5FFF') }}" placeholder="#RRGGBB">
                            </div>
                            <small class="text-muted">Use format: #RRGGBB</small>
                            @error('primary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="secondary_color" class="form-label">Secondary color</label>
                            <div class="input-group input-group-sm">
                                <input id="secondary_color_picker" type="color" class="form-control form-control-color @error('secondary_color') is-invalid @enderror" value="{{ old('secondary_color', $profile['secondary_color'] ?? '#111827') }}">
                                <input id="secondary_color" name="secondary_color" class="form-control form-control-sm @error('secondary_color') is-invalid @enderror" value="{{ old('secondary_color', $profile['secondary_color'] ?? '#111827') }}" placeholder="#RRGGBB">
                            </div>
                            <small class="text-muted">Use format: #RRGGBB</small>
                            @error('secondary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo upload</label>
                            <input id="logo" type="file" name="logo" class="form-control form-control-sm @error('logo') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-1">Current: {{ $profile['logo_path'] ?? '/storage/branding/logo.svg' }}</small>
                            <input type="hidden" name="logo_path" value="{{ old('logo_path', $profile['logo_path'] ?? '/storage/branding/logo.svg') }}">
                            @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('logo_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="logo_dark" class="form-label">Dark logo upload</label>
                            <input id="logo_dark" type="file" name="logo_dark" class="form-control form-control-sm @error('logo_dark') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-1">Current: {{ $profile['logo_dark_path'] ?? '/storage/branding/logo-dark.svg' }}</small>
                            <input type="hidden" name="logo_dark_path" value="{{ old('logo_dark_path', $profile['logo_dark_path'] ?? '/storage/branding/logo-dark.svg') }}">
                            @error('logo_dark')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('logo_dark_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="favicon" class="form-label">Favicon upload</label>
                            <input id="favicon" type="file" name="favicon" class="form-control form-control-sm @error('favicon') is-invalid @enderror" accept=".ico,image/png,image/svg+xml">
                            <small class="text-muted d-block mt-1">Current: {{ $profile['favicon_path'] ?? '/storage/branding/favicon.ico' }}</small>
                            <input type="hidden" name="favicon_path" value="{{ old('favicon_path', $profile['favicon_path'] ?? '/storage/branding/favicon.ico') }}">
                            @error('favicon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('favicon_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="social_share_image" class="form-label">Social share image upload</label>
                            <input id="social_share_image" type="file" name="social_share_image" class="form-control form-control-sm @error('social_share_image') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-1">Current: {{ $profile['social_share_image_path'] ?? '/storage/branding/og-default.jpg' }}</small>
                            <input type="hidden" name="social_share_image_path" value="{{ old('social_share_image_path', $profile['social_share_image_path'] ?? '/storage/branding/og-default.jpg') }}">
                            @error('social_share_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('social_share_image_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

             

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">SEO Defaults</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="seo_title_template" class="form-label">SEO title template</label>
                            <input id="seo_title_template" name="seo_title_template" class="form-control form-control-sm @error('seo_title_template') is-invalid @enderror" value="{{ old('seo_title_template', $profile['seo_title_template'] ?? '{page_title} | {store_name}') }}" placeholder="{page_title} | {store_name}">
                            <small class="text-muted">Available placeholders: {page_title}, {store_name}</small>
                            @error('seo_title_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="seo_meta_description" class="form-label">Default meta description</label>
                            <textarea id="seo_meta_description" name="seo_meta_description" rows="3" class="form-control form-control-sm @error('seo_meta_description') is-invalid @enderror">{{ old('seo_meta_description', $profile['seo_meta_description'] ?? '') }}</textarea>
                            @error('seo_meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="seo_meta_keywords" class="form-label">Default meta keywords</label>
                            <input id="seo_meta_keywords" name="seo_meta_keywords" class="form-control form-control-sm @error('seo_meta_keywords') is-invalid @enderror" value="{{ old('seo_meta_keywords', $profile['seo_meta_keywords'] ?? '') }}" placeholder="ecommerce, herbs, organic">
                            @error('seo_meta_keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="seo_robots" class="form-label">Robots default</label>
                            <select id="seo_robots" name="seo_robots" class="form-select form-select-sm @error('seo_robots') is-invalid @enderror">
                                @php($seoRobots = old('seo_robots', $profile['seo_robots'] ?? 'index,follow'))
                                <option value="index,follow" @selected($seoRobots === 'index,follow')>index,follow</option>
                                <option value="noindex,follow" @selected($seoRobots === 'noindex,follow')>noindex,follow</option>
                                <option value="index,nofollow" @selected($seoRobots === 'index,nofollow')>index,nofollow</option>
                                <option value="noindex,nofollow" @selected($seoRobots === 'noindex,nofollow')>noindex,nofollow</option>
                            </select>
                            @error('seo_robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="seo_canonical_base_url" class="form-label">Canonical base URL</label>
                            <input id="seo_canonical_base_url" type="url" name="seo_canonical_base_url" class="form-control form-control-sm @error('seo_canonical_base_url') is-invalid @enderror" value="{{ old('seo_canonical_base_url', $profile['seo_canonical_base_url'] ?? config('app.url')) }}" placeholder="https://example.com">
                            @error('seo_canonical_base_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="seo_og_image" class="form-label">Default OG image upload</label>
                            <input id="seo_og_image" type="file" name="seo_og_image" class="form-control form-control-sm @error('seo_og_image') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-1">Current: {{ $profile['seo_og_image_path'] ?? '/storage/branding/og-default.jpg' }}</small>
                            <input type="hidden" name="seo_og_image_path" value="{{ old('seo_og_image_path', $profile['seo_og_image_path'] ?? '/storage/branding/og-default.jpg') }}">
                            @error('seo_og_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('seo_og_image_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function settingErrors(xhr) {
            const body = xhr.responseJSON || {};
            return body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Request failed.');
        }

        $('#primary_color_picker').on('input', function () {
            $('#primary_color').val($(this).val().toUpperCase());
        });

        $('#primary_color').on('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test($(this).val())) {
                $('#primary_color_picker').val($(this).val());
            }
        });

        $('#secondary_color_picker').on('input', function () {
            $('#secondary_color').val($(this).val().toUpperCase());
        });

        $('#secondary_color').on('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test($(this).val())) {
                $('#secondary_color_picker').val($(this).val());
            }
        });

        $('#store-profile-form').on('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.set('_method', 'PUT');

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
            })
                .done((body) => adminToast(body.message || 'Store profile settings updated.'))
                .fail((xhr) => adminToast(settingErrors(xhr), 'error'));
        });
    </script>
@endpush
