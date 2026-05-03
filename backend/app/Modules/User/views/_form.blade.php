<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Name</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone</label>
                <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone ?? '') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                @php($status = old('status', $user->status ?? 'active'))
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" @if($requirePassword) required @endif>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @unless($requirePassword)
                <div class="col-md-6 d-flex align-items-end">
                    <p class="text-secondary mb-2">Leave password blank to keep the current password.</p>
                </div>
            @endunless

            <div class="col-12">
                <label class="form-label">Roles</label>
                @php($selectedRoles = old('roles', isset($user) ? $user->getRoleNames()->all() : []))
                <div class="row g-2">
                    @foreach ($roles as $roleName => $roleLabel)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    id="role_{{ $roleName }}"
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $roleName }}"
                                    class="form-check-input @error('roles') is-invalid @enderror @error('roles.*') is-invalid @enderror"
                                    @checked(in_array($roleName, $selectedRoles, true))
                                >
                                <label class="form-check-label" for="role_{{ $roleName }}">{{ $roleLabel }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('roles')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('roles.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $button }}</button>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function userFormErrors(xhr) {
                const body = xhr.responseJSON || {};

                return body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Request failed.');
            }

            $('.ajax-user-form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                })
                    .done((body) => {
                        adminToast(body.message || 'User saved successfully.');

                        setTimeout(() => {
                            window.location.href = $(this).data('redirect');
                        }, 700);
                    })
                    .fail((xhr) => adminToast(userFormErrors(xhr), 'error'));
            });
        </script>
    @endpush
@endonce
