<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Services\UserService;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserService $service
    ) {
    }

    public function index()
    {
        return view('user::index', [
            'users' => $this->service->paginate(15),
        ]);
    }
}
