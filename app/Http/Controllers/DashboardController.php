<?php

namespace App\Http\Controllers;

use App\Domain\Missions\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'users_count' => User::query()->count(),
                    'open_disputes_count' => Dispute::query()->where('status', DisputeStatus::Open)->count(),
                    'missions_in_progress' => Mission::query()->where('status', 'in_progress')->count(),
                ],
            ]);
        }

        return Inertia::render('Dashboard', [
            'isClient' => $user->isClient(),
            'isFreelancer' => $user->isFreelancer(),
        ]);
    }
}
