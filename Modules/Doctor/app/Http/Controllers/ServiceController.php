<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Doctor\app\Http\Requests\InsuranceProviderRequest as ServiceRequest;
use Modules\Doctor\app\Models\Service;

class ServiceController extends Controller {
    use RedirectHelperTrait;

    public function index(Request $request) {
        checkAdminHasPermissionAndThrowException('service.view');
        $query = Service::query();
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        $services = $query->orderBy('name')->paginate(15);
        return view('doctor::service.index', compact('services'));
    }

    public function store(ServiceRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('service.store');
        Service::create($request->validated());
        return $this->redirectWithMessage(RedirectType::CREATE->value);
    }

    public function update(ServiceRequest $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('service.update');
        $item = Service::findOrFail($id);
        $item->update($request->validated());
        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('service.delete');
        $item = Service::findOrFail($id);
        $item->delete();
        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('service.update');
        $item = Service::findOrFail($id);
        $item->update(['status' => ! $item->status]);
        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }
}
