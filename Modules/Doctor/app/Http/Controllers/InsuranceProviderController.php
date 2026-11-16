<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Doctor\app\Http\Requests\InsuranceProviderRequest;
use Modules\Doctor\app\Models\InsuranceProvider;

class InsuranceProviderController extends Controller {
    use RedirectHelperTrait;

    public function index(Request $request) {
        checkAdminHasPermissionAndThrowException('insuranceprovider.view');
        $query = InsuranceProvider::query();

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        $insuranceProviders = $query->orderBy('name')->paginate(15);
        return view('doctor::insurance_provider.index', compact('insuranceProviders'));
    }

    public function store(InsuranceProviderRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('insuranceprovider.store');
        InsuranceProvider::create($request->validated());
        return $this->redirectWithMessage(RedirectType::CREATE->value);
    }

    public function update(InsuranceProviderRequest $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('insuranceprovider.update');
        $provider = InsuranceProvider::findOrFail($id);
        $provider->update($request->validated());
        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('insuranceprovider.delete');
        $provider = InsuranceProvider::findOrFail($id);
        $provider->delete();
        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('insuranceprovider.update');
        $provider = InsuranceProvider::findOrFail($id);
        $provider->update(['status' => ! $provider->status]);
        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }
}
