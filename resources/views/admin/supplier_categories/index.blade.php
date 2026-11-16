@extends('admin.master_layout')
@section('title')<title>{{ __('Supplier Categories') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section">
    <x-admin.breadcrumb title="{{ __('Supplier Categories') }}" />
    <div class="section-body">
        <div class="card"><div class="card-header"><h4>{{ __('Add a supplier category') }}</h4></div><div class="card-body">
            <form method="POST" action="{{ route('admin.supplier-categories.store') }}">@csrf
                <div class="form-row"><div class="form-group col-md-5"><label>{{ __('Name') }}</label><input name="name" class="form-control" required maxlength="255"></div>
                <div class="form-group col-md-4"><label>{{ __('Parent category') }}</label><select name="parent_id" class="form-control"><option value="">{{ __('No parent') }}</option>@foreach($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach</select></div>
                <div class="form-group col-md-2"><label>{{ __('Order') }}</label><input name="sort_order" type="number" class="form-control" min="0" value="0"></div>
                <div class="form-group col-md-1 d-flex align-items-end"><label><input name="is_active" type="checkbox" value="1" checked> {{ __('Active') }}</label></div></div>
                <button class="btn btn-primary">{{ __('Create category') }}</button>
            </form>
        </div></div>
        <div class="card"><div class="card-header"><h4>{{ __('Categories used by supplier profiles and products') }}</h4></div><div class="card-body table-responsive">
            <table class="table"><thead><tr><th>{{ __('Category') }}</th><th>{{ __('Parent') }}</th><th>{{ __('Order') }}</th><th>{{ __('Active') }}</th><th>{{ __('Save') }}</th></tr></thead><tbody>
                @forelse($categories as $category)@php($formId = 'supplier-category-'.$category->id)<tr>
                    <td><input form="{{ $formId }}" name="name" class="form-control" value="{{ $category->name }}" required maxlength="255"></td>
                    <td><select form="{{ $formId }}" name="parent_id" class="form-control"><option value="">{{ __('No parent') }}</option>@foreach($parents as $parent)@if($parent->id !== $category->id)<option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>@endif @endforeach</select></td>
                    <td><input form="{{ $formId }}" name="sort_order" type="number" class="form-control" min="0" value="{{ $category->sort_order }}"></td>
                    <td><input form="{{ $formId }}" name="is_active" type="checkbox" value="1" @checked($category->is_active)></td>
                    <td><form id="{{ $formId }}" method="POST" action="{{ route('admin.supplier-categories.update', $category) }}">@csrf @method('PUT')<button class="btn btn-sm btn-primary">{{ __('Save') }}</button></form></td>
                </tr>@empty<tr><td colspan="5">{{ __('No categories created yet.') }}</td></tr>@endforelse
            </tbody></table>{{ $categories->links() }}
        </div></div>
        <p class="text-muted small">{{ __('Inactive categories stay attached to existing records, but cannot be selected for new listings or public directory filters.') }}</p>
    </div>
</section></div>
@endsection
