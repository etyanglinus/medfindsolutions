<?php

namespace App\Http\Controllers;

use App\Services\ProviderEmailPreferences;
use App\Services\ProviderPaystackService;
use Illuminate\Http\Request;

/**
 * The unsubscribe page linked from the monthly report. Opening the link only shows the choice
 * (mail scanners open links); the change is made with the button (POST). Mail apps that support
 * one-click unsubscribe (List-Unsubscribe-Post) POST to the same signed URL without a CSRF token.
 */
class ProviderEmailPreferenceController extends Controller
{
    public function show(string $type, int $id, ProviderEmailPreferences $preferences)
    {
        $client = ProviderPaystackService::model($type)::findOrFail($id);
        return view('provider.email-preferences', [
            'client' => $client,
            'subscribed' => $preferences->wantsMonthlyReport($type, $id),
            'actionUrl' => request()->fullUrl(),
        ]);
    }

    public function update(Request $request, string $type, int $id, ProviderEmailPreferences $preferences)
    {
        ProviderPaystackService::model($type)::findOrFail($id);
        $on = $request->input('monthly_report') === '1'; // one-click unsubscribe sends no such field
        $preferences->setMonthlyReport($type, $id, $on);
        if ($request->has('List-Unsubscribe')) return response()->noContent();
        return redirect()->to($request->fullUrl())->with(['message' => $on ? __('You will receive the monthly report again.') : __('You have been unsubscribed from the monthly report.'), 'alert-type' => 'success']);
    }
}
