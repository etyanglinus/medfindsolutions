<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Tafuta\TafutaAssistant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TafutaSettingsController extends Controller
{
    public function edit()
    {
        checkAdminHasPermissionAndThrowException('setting.view');
        $setting = Cache::get('setting');
        return view('admin.tafuta.settings', [
            'on' => (string) ($setting->tafuta_status ?? '0') === '1',
            'dailyLimit' => (int) ($setting->tafuta_daily_limit ?? 300),
            'keySet' => filled(config('services.tafuta.api_key')),
            'model' => config('services.tafuta.model'),
            'answeredToday' => (int) Cache::get(app(TafutaAssistant::class)->dailyKey(), 0),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        $data = $request->validate(['tafuta_status' => ['nullable', 'boolean'], 'tafuta_daily_limit' => ['required', 'integer', 'min:0', 'max:100000']]);
        foreach (['tafuta_status' => $request->boolean('tafuta_status') ? '1' : '0', 'tafuta_daily_limit' => (string) $data['tafuta_daily_limit']] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }
        Cache::forget('setting');
        Cache::rememberForever('setting', fn () => (object) DB::table('settings')->pluck('value', 'key')->all());
        $live = TafutaAssistant::enabled();
        return back()->with(['message' => $live ? __('Tafuta is on and visible on the website.') : ($request->boolean('tafuta_status') ? __('Saved, but Tafuta stays hidden until the API key is added to the server\'s .env file.') : __('Tafuta is off.')), 'alert-type' => $live || !$request->boolean('tafuta_status') ? 'success' : 'warning']);
    }
}
