<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PublicDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;

class IncompleteListingController extends Controller
{
    public function __invoke(Request $request, string $type, int $id): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException($type === 'doctor' ? 'doctor.update' : 'facility.update');

        $request->validate(['allow_incomplete_listing' => ['required', 'boolean']]);
        $model = $type === 'doctor' ? Doctor::class : Clinic::class;
        $client = $model::query()->findOrFail($id);
        $client->allow_incomplete_listing = $request->boolean('allow_incomplete_listing');
        $client->save();

        PublicDirectory::forget();

        return back()->with(['message' => __('Listing preference saved.'), 'alert-type' => 'success']);
    }
}
