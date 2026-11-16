<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Doctor\app\Http\Requests\LicensingBodyRequest;
use Modules\Doctor\app\Models\LicensingBody;

class LicensingBodyController extends Controller {
    use RedirectHelperTrait;

    public function index(Request $request) {
        checkAdminHasPermissionAndThrowException('licensingbody.view');
        $query = LicensingBody::query();

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        $licensingBodies = $query->orderBy('type')->paginate(15);
        return view('doctor::licensing_body.index', compact('licensingBodies'));
    }

    public function store(LicensingBodyRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('licensingbody.store');
        LicensingBody::create($request->validated());
        return $this->redirectWithMessage(RedirectType::CREATE->value);
    }

    public function update(LicensingBodyRequest $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('licensingbody.update');
        $body = LicensingBody::findOrFail($id);
        $body->update($request->validated());
        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('licensingbody.delete');
        $body = LicensingBody::findOrFail($id);
        $body->delete();
        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('licensingbody.update');
        $body = LicensingBody::findOrFail($id);
        $body->update(['status' => ! $body->status]);
        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }
}
