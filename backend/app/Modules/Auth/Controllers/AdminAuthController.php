<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\AdminLoginRequest;
use App\Modules\Auth\Requests\PasswordResetLinkRequest;
use App\Modules\Auth\Requests\PasswordResetRequest;
use App\Modules\Auth\Services\AdminAuthService;
use App\Modules\Auth\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $adminAuthService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth::login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $this->adminAuthService->login(
            $request,
            $request->validated('email'),
            $request->validated('password'),
            (bool) $request->validated('remember', false)
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->adminAuthService->logout($request);

        return redirect()->route('admin.login');
    }

    public function showForgotPasswordForm(): View
    {
        return view('auth::forgot-password');
    }

    public function sendResetLink(PasswordResetLinkRequest $request): RedirectResponse
    {
        $this->passwordResetService->sendAdminResetLink($request->validated('email'));

        return back()->with('status', 'If the email exists, a password reset link has been sent.');
    }

    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('auth::reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(PasswordResetRequest $request): RedirectResponse
    {
        $this->passwordResetService->resetAdminPassword($request->validated());

        return redirect()
            ->route('admin.login')
            ->with('status', 'Password reset successfully. You can now sign in.');
    }
}
