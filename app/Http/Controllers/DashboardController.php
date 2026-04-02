<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');

        $activePlan     = null;
        $plans          = [];
        $myApplications = [];

        if (!$isAdmin) {
            $activePlan = $user->userPlans()
                ->where('is_active', true)
                ->where('end_date', '>=', now()->toDateString())
                ->with('plan')
                ->latest()
                ->first();

            $plans = Plan::where('is_active', true)
                ->orderBy('amount')
                ->get();

            $myApplications = Application::where('user_id', $user->id)
                ->with(['tender:id,title,slug,tender_no'])
                ->latest()
                ->take(10)
                ->get();
        }

        return Inertia::render('Dashboard', [
            'isAdmin'        => $isAdmin,
            'activePlan'     => $activePlan,
            'plans'          => $plans,
            'myApplications' => $myApplications,
        ]);
    }
}
