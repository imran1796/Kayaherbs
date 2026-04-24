<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $users = User::query();

        return view('admin.dashboard.index', [
            'stats' => [
                'total_users' => (clone $users)->count(),
                'active_users' => (clone $users)->where('status', 'active')->count(),
                'inactive_users' => (clone $users)->where('status', '!=', 'active')->count(),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'status', 'created_at']),
        ]);
    }
}
