<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\CustomerLoginRequest;
use App\Modules\Auth\Requests\CustomerRegisterRequest;
use App\Modules\Auth\Requests\PasswordResetLinkRequest;
use App\Modules\Auth\Requests\PasswordResetRequest;
use App\Modules\Auth\Services\CustomerAuthService;
use App\Modules\Auth\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        return ApiResponse::success(
            $this->customerAuthService->register($request->validated()),
            'Customer registered successfully.',
            201
        );
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        return ApiResponse::success(
            $this->customerAuthService->login(
                $request->validated('email'),
                $request->validated('password')
            ),
            'Customer authenticated successfully.'
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => $this->customerAuthService->profile($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->customerAuthService->logout($request->user());

        return ApiResponse::success(null, 'Customer logged out successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->customerAuthService->logoutAll($request->user());

        return ApiResponse::success(null, 'Customer logged out from all devices successfully.');
    }

    public function forgotPassword(PasswordResetLinkRequest $request): JsonResponse
    {
        $this->passwordResetService->sendCustomerResetLink($request->validated('email'));

        return ApiResponse::success(null, 'If the email exists, a password reset link has been sent.');
    }

    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $this->passwordResetService->resetCustomerPassword($request->validated());

        return ApiResponse::success(null, 'Password reset successfully.');
    }
}
