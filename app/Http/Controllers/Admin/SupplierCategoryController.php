<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\MedicalSupplier\app\Models\SupplierCategory;

class SupplierCategoryController extends Controller
{
    public function index(): View
    {
        checkAdminHasPermissionAndThrowException('supplier.view');
        $categories = SupplierCategory::with('parent')->orderBy('sort_order')->orderBy('name')->paginate(50);
        $parents = SupplierCategory::whereNull('parent_id')->orderBy('name')->get();
        return view('admin.supplier_categories.index', compact('categories', 'parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('supplier.store');
        SupplierCategory::create($this->validated($request));
        return back()->with(['message' => __('Supplier category created.'), 'alert-type' => 'success']);
    }

    public function update(Request $request, SupplierCategory $category): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('supplier.update');
        $category->update($this->validated($request, $category));
        return back()->with(['message' => __('Supplier category updated.'), 'alert-type' => 'success']);
    }

    private function validated(Request $request, ?SupplierCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::exists('medical_supplier_categories', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $parentId = $data['parent_id'] ?? null;
        while ($parentId) {
            abort_if($category && (int) $parentId === (int) $category->id, 422, __('A category cannot be nested under itself or one of its descendants.'));
            $parentId = SupplierCategory::whereKey($parentId)->value('parent_id');
        }
        $base = Str::slug($data['name']);
        $slug = $base;
        $suffix = 2;
        while (SupplierCategory::where('slug', $slug)->when($category, fn ($q) => $q->where('id', '!=', $category->id))->exists()) $slug = $base.'-'.$suffix++;
        return [
            'name' => $data['name'], 'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $request->boolean('is_active'), 'slug' => $slug,
        ];
    }
}
