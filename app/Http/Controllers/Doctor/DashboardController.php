<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\ProfileView;

class DashboardController extends Controller {
    public function index(Request $request): View {
        $doctor = doctorAuth();

        $data = [];
        $data['subscription'] = app(\Modules\Doctor\app\Services\SubscriptionAccessService::class)->activeDoctorSubscription($doctor);

        $viewDays = collect(range(29, 0))->map(fn ($offset) => now()->subDays($offset)->toDateString());
        $profileViews = ProfileView::query()->selectRaw('viewed_on, COUNT(*) as total')
            ->where('profile_type', 'doctor')->where('profile_id', $doctor->id)
            ->whereBetween('viewed_on', [$viewDays->first(), $viewDays->last()])
            ->groupBy('viewed_on')->pluck('total', 'viewed_on');
        $data['profile_views_total'] = ProfileView::where('profile_type', 'doctor')->where('profile_id', $doctor->id)->count();
        $data['profile_views_30_days'] = $profileViews->sum();
        $data['profile_view_labels'] = $viewDays->map(fn ($day) => Carbon::parse($day)->format('d M'))->values();
        $data['profile_view_data'] = $viewDays->map(fn ($day) => (int) ($profileViews[$day] ?? 0))->values();

        return view('doctor.dashboard', $data);
    }
}
