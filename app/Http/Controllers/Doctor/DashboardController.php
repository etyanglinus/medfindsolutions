<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Appointment\app\Models\Appointment;
use App\Models\ProfileView;

class DashboardController extends Controller {
    public function index(Request $request): View {
        $doctor = doctorAuth();

        $dataCal = [];
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        if ($request->filled('year') && $request->filled('month')) {
            $year = $request->input('year');
            $month = $request->input('month');

            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        } elseif ($request->filled('year')) {
            $year = $request->input('year');

            $start = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $end = $start->copy()->endOfYear();
        }

        $data = Appointment::selectRaw("DATE(created_at) as month, SUM(appointment_fee_usd) as total_price")
            ->paymentSuccess()->whereBetween('created_at', [$start, $end])->where('doctor_id', $doctor->id)->groupBy('month')->get();

        $dates = [];

        while ($start <= $end) {
            $dates[] = $start->toDateString();
            $start->addDay();
        }

        $dataCal = array_fill_keys($dates, 0);

        foreach ($data as $item) {
            $dataCal[$item->month] = $item->total_price;
        }

        $data = [];
        $data['subscription'] = app(\Modules\Doctor\app\Services\SubscriptionAccessService::class)->activeDoctorSubscription($doctor);
        $data['monthly_data'] = json_encode(array_values($dataCal));

        $data['today_appointment'] = Appointment::where(['doctor_id' => $doctor->id, 'date' => date('Y-m-d')])->paymentSuccess()->notTreated()->get();
        $data['new_appointment'] = Appointment::where('doctor_id', $doctor->id)->paymentSuccess()->notTreated()->count();
        $data['success_appointment'] = Appointment::where('doctor_id', $doctor->id)->paymentSuccess()->treated()->count();

        $data['totalEarning'] = Appointment::paymentSuccess()->where('doctor_id', $doctor->id)->sum('appointment_fee_usd');

        $viewDays = collect(range(29, 0))->map(fn ($offset) => now()->subDays($offset)->toDateString());
        $profileViews = ProfileView::query()->selectRaw('viewed_on, COUNT(*) as total')
            ->where('profile_type', 'doctor')->where('profile_id', $doctor->id)
            ->whereBetween('viewed_on', [$viewDays->first(), $viewDays->last()])
            ->groupBy('viewed_on')->pluck('total', 'viewed_on');
        $data['profile_views_total'] = ProfileView::where('profile_type', 'doctor')->where('profile_id', $doctor->id)->count();
        $data['profile_views_30_days'] = $profileViews->sum();
        $data['profile_view_labels'] = $viewDays->map(fn ($day) => Carbon::parse($day)->format('d M'))->values();
        $data['profile_view_data'] = $viewDays->map(fn ($day) => (int) ($profileViews[$day] ?? 0))->values();

        $data['oldestYear'] = Carbon::parse(Appointment::select('created_at')->where('doctor_id', $doctor->id)->orderBy('created_at', 'asc')->first()?->created_at)->year ?? Carbon::now()->year;
        $data['latestYear'] = Carbon::parse(Appointment::select('created_at')->where('doctor_id', $doctor->id)->orderBy('created_at', 'desc')->first()?->created_at)->year ?? Carbon::now()->year;

        return view('doctor.dashboard', $data);
    }
}
