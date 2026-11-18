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
        $dash = app(\App\Services\ProviderDashboard::class)->build('doctor', $doctor, (int) $request->query('days', 30));
        return view('doctor.dashboard', ['doctor' => $doctor, 'dash' => $dash, 'subscription' => $dash['subscription']]);
    }
}
