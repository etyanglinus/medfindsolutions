@extends('admin.master_layout')
@section('title')
    <title>{{ __('Overviews') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Overviews') }}" :list="[
                __('Dashboard') => route('admin.dashboard'),
                __('Overviews') => '#',
            ]" />
            <div class="section-body row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="lang_list_top">
                                <ul class="lang_list">
                                    @foreach ($languages = allLanguages() as $language)
                                        <li><a id="{{ request('code') == $language->code ? 'selected-language' : '' }}"
                                                href="{{ route('admin.counter.index', ['code' => $language->code]) }}"><i
                                                    class="fas {{ request('code') == $language->code ? 'fa-eye' : 'fa-edit' }}"></i>
                                                {{ $language->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="mt-2 alert alert-danger" role="alert">
                                @php
                                    $current_language = $languages->where('code', request()->get('code'))->first();
                                @endphp
                                <p>{{ __('Your editing mode') }}:<b> {{ $current_language?->name }}</b></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                @if (checkAdminHasPermission('counter.store'))
                                    <div>
                                        <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#itemModal"
                                            class="btn btn-primary itemModalClose"><i class="fa fa-plus"></i>
                                            {{ __('Add New') }}</a>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th width="5%">{{ __('SN') }}</th>
                                                <th width="30%">{{ __('Title') }}</th>
                                                <th width="15%">{{ __('Quantity') }}</th>
                                                <th width="20%">{{ __('Status') }}</th>
                                                <th width="15%">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($counters as $item)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>{{ $item?->getTranslation($code)?->title }}</td>
                                                    <td>{{ $item->qty }}</td>
                                                    <td>
                                                        <input onchange="changeStatus({{ $item->id }})"
                                                            id="status_toggle" type="checkbox"
                                                            {{ $item->status ? 'checked' : '' }} data-toggle="toggle"
                                                            data-onlabel="{{ __('Active') }}"
                                                            data-offlabel="{{ __('Inactive') }}" data-onstyle="success"
                                                            data-offstyle="danger">
                                                    </td>
                                                    <td>
                                                        @if (checkAdminHasPermission('counter.update'))
                                                            <x-admin.edit-button
                                                                onclick="editItemData({{ $item->id }}, '{{ $item?->getTranslation($code)?->title }}', '{{ asset($item->icon) }}', '{{ $item->qty }}')" />
                                                        @endif
                                                        @if (checkAdminHasPermission('counter.delete'))
                                                            <x-admin.delete-button :id="$item->id" onclick="deleteData" />
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <x-empty-table :name="__('Item')" route="admin.counter.index" create="no"
                                                    :message="__('No data found!')" colspan="7" />
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $counters->onEachSide(0)->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @if (checkAdminHasPermission('counter.store') || checkAdminHasPermission('counter.update'))
        <div tabindex="-1" role="dialog" id="itemModal" class="modal fade">
            <div class="modal-dialog" role="document">
                <form class="modal-content" id="ItemForm" action="{{ route('admin.counter.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Create Overview') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <x-admin.form-input type="hidden" name="code"
                            value="{{ request('code') ?? getSessionLanguage() }}" />
                        @if ($code == $languages->first()->code)
                            <div class="form-group col-md-6">
                                <x-admin.form-image-preview recommended="32X32" class="icon-preview" div_id="icon-preview" label_id="icon-label"
                                    input_id="icon-upload" name="icon" label="{{ __('Icon') }}"
                                    button_label="{{ __('Upload Icon') }}" required="0" />
                            </div>
                        @endif
                        <div class="form-group col-12">
                            <x-admin.form-input id="itemTitle" name="title" label="{{ __('Title') }}"
                                placeholder="{{ __('Enter Title') }}" required="true" />
                        </div>
                        <div class="form-group col-12">
                            <x-admin.form-input type="number" id="itemQty" name="qty" label="{{ __('Quantity') }}"
                                placeholder="{{ __('Enter Quantity') }}" required="true" />
                        </div>

                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <x-admin.button variant="danger" class="itemModalClose" data-bs-dismiss="modal"
                            text="{{ __('Close') }}" />
                        <x-admin.button id="submitBtn" type="submit" text="{{ __('Save') }}" />
                    </div>
                </form>
            </div>
        </div>
    @endif
    <x-admin.delete-modal />
@endsection
@if ($code == $languages->first()->code)
    @push('css')
        <style>
            .icon-preview {
                width: 100px;
                height: 100px;
                background-repeat: no-repeat;
            }

            .icon-preview label {
                width: 90px;
                height: 36px;
                line-height: 36px;
                font-size: 10px;
            }
        </style>
    @endpush
@endif

@push('js')
    <script src="{{ asset('backend/js/jquery.uploadPreview.min.js') }}"></script>
    <script>
        $.uploadPreview({
            input_field: "#icon-upload",
            preview_box: "#icon-preview",
            label_field: "#icon-label",
            label_default: "{{ __('Choose Icon') }}",
            label_selected: "{{ __('Change Icon') }}",
            no_label: false,
            success_callback: null
        });
    </script>
    <script>
        "use strict"

        function deleteData(id) {
            $("#deleteForm").attr("action", '{{ url('/admin/counter/') }}' + "/" + id);
        }
        "use strict"
        $('.itemModalClose').on('click', function() {
            var default_lang = "{{ $code == $languages->first()->code }}";
            $("#ItemForm").attr("action", "{{ url('/admin/counter') }}");
            $("#itemModal .modal-title").text("{{ __('Create Overview') }}");
            $("#submitBtn").text("{{ __('Save') }}");
            $("#itemTitle").val('');
            if (default_lang) {
                $("#icon-preview").css('background-image', 'none');
            }
            $("#itemQty").val('');
            $('#itemModal').modal('hide');
        });

        "use strict"

        function editItemData(id, title, icon, qty) {
            var default_lang = "{{ $code == $languages->first()->code }}";
            $("#ItemForm").attr("action", "{{ url('/admin/counter') }}" + '/' + id);
            $("#itemModal .modal-title").text("{{ __('Edit Overview') }}");
            $("#submitBtn").text("{{ __('Update') }}");
            $("#itemTitle").val(title);
            if (default_lang) {
                $("#icon-preview").css({
                    'background-image': 'url(' + icon + ')'
                });
            }
            $("#itemQty").val(qty);
            $('#itemModal').modal('show');
        }
        "use strict"

        function changeStatus(id) {
            var isDemo = "{{ env('APP_MODE') ?? 'LIVE' }}"
            if (isDemo == 'DEMO') {
                toastr.error("{{ __('This Is Demo Version. You Can Not Change Anything') }}");
                return;
            }
            $.ajax({
                type: "put",
                data: {
                    _token: '{{ csrf_token() }}',
                },
                url: "{{ url('/admin/counter/status-update') }}" + "/" + id,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.warning(response.message);
                    }
                },
                error: function(err) {
                    console.log(err);
                }
            });
        }
    </script>
@endpush
