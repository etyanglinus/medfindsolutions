<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LicenceVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin switch: list specialists and facilities only when they hold an approved, unexpired licence. */
class ListingVerificationController extends Controller
{
    public function update(Request $request, LicenceVerification $licences): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('doctor.update');
        $on = $request->boolean('required');
        $licences->setRequired($on);
        return back()->with(['message' => $on
            ? __('Only specialists and facilities with an approved, unexpired licence now appear in the directory.')
            : __('Licence approval is no longer required to appear in the directory.'), 'alert-type' => 'success']);
    }
}
