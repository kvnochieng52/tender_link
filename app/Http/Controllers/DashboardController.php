<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Institution;
use App\Models\Plan;
use App\Models\Tender;
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
        $adminStats     = [];
        $recentTenders  = [];

        if ($isAdmin) {
            $adminStats = [
                [
                    'title' => 'Active Tenders',
                    'value' => Tender::whereHas('status', fn($q) => $q->where('name', 'Active'))
                        ->where(fn($q) => $q->whereNull('closing_date_and_time')->orWhere('closing_date_and_time', '>', now()))
                        ->count(),
                    'note'  => 'Open opportunities',
                    'icon'  => 'fas fa-briefcase',
                    'color' => 'success',
                ],
                [
                    'title' => 'Expired Tenders',
                    'value' => Tender::where('closing_date_and_time', '<=', now())->count(),
                    'note'  => 'Past closing date',
                    'icon'  => 'fas fa-hourglass-end',
                    'color' => 'danger',
                ],
                [
                    'title' => 'Total Applications',
                    'value' => Application::count(),
                    'note'  => 'All submissions',
                    'icon'  => 'fas fa-file-signature',
                    'color' => 'primary',
                ],
                [
                    'title' => 'Institutions',
                    'value' => Institution::count(),
                    'note'  => 'Registered bodies',
                    'icon'  => 'fas fa-building',
                    'color' => 'info',
                ],
            ];

            $recentTenders = Tender::with([
                'status:id,name',
                'institution:id,institution_name',
                'county:id,name',
            ])
                ->latest()
                ->take(5)
                ->get(['id', 'title', 'slug', 'tender_status_id', 'institution_id', 'county_id', 'closing_date_and_time']);
        } else {
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
            'adminStats'     => $adminStats,
            'recentTenders'  => $recentTenders,
            'activePlan'     => $activePlan,
            'plans'          => $plans,
            'myApplications' => $myApplications,
        ]);
    }
}
