@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Social Links') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Social Links') }}" :list="[
                __('Dashboard') => route('doctor.dashboard'),
                __('Social Links') => '#',
            ]" />
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            @if ($social_links->count() < (int) $setting?->doctor_social_links_limit)
                                <div class="card-header d-flex justify-content-between">
                                    <div>
                                        <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#itemModal"
                                            class="btn btn-primary"><i class="fa fa-plus"></i> {{ __('Add New') }}</a>
                                    </div>
                                </div>
                            @endif
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th width="10%">{{ __('SN') }}</th>
                                                <th width="20%">{{ __('Icon') }}</th>
                                                <th width="30%">{{ __('Link') }}</th>
                                                <th width="20%">{{ __('Status') }}</th>
                                                <th width="20%">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($social_links as $item)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td><img src="{{ asset($item?->icon) }}" width="30px" height="auto"
                                                            class="my-2">
                                                    </td>
                                                    <td>{{ $item?->link }}</td>
                                                    <td>
                                                        <input onchange="changeStatus({{ $item->id }})"
                                                            id="status_toggle" type="checkbox"
                                                            {{ $item->status ? 'checked' : '' }} data-toggle="toggle"
                                                            data-onlabel="{{ __('Active') }}"
                                                            data-offlabel="{{ __('Inactive') }}" data-onstyle="success"
                                                            data-offstyle="danger">
                                                    </td>
                                                    <td>
                                                        <a href="javascript:;"
                                                            onclick="editItemData({{ $item->id }}, '{{ asset($item?->icon) }}', '{{ $item?->link }}')"
                                                            class="btn btn-warning btn-sm"><i class="fa fa-edit"
                                                                aria-hidden="true"></i></a>
                                                        <x-admin.delete-button :id="$item->id" onclick="deleteData" />
                                                </tr>
                                            @empty
                                                <x-empty-table :name="__('Item')" route="admin.medicine.index" create="no"
                                                    :message="__('No data found!')" colspan="7" />
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div tabindex="-1" role="dialog" id="itemModal" class="modal fade">
        <div class="modal-dialog" role="document">
            <form class="modal-content" id="ItemForm" action="{{ route('doctor.social-link.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Social Link') }}</h5>
                    <button type="button" class="btn-close itemModalClose" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group image-field">
                        <x-admin.form-image-preview recommended="24X24" name="icon" label="{{ __('Icon') }}" />
                    </div>
                    <div class="form-group">
                        <x-admin.form-input id="itemLink" name="link" label="{{ __('Link') }}"
                            placeholder="{{ __('Enter link') }}" required="true" />
                    </div>

                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <x-admin.button variant="danger" data-bs-dismiss="modal" class="itemModalClose"
                        text="{{ __('Close') }}" />
                    <x-admin.button class="submit-btn" type="submit" text="{{ __('Save') }}" />
                </div>
            </form>
        </div>
    </div>
    <x-admin.delete-modal />
@endsection
@push('js')
    <script src="{{ asset('backend/js/jquery.uploadPreview.min.js') }}"></script>
    <script>
        "use strict";
        $.uploadPreview({
            input_field: "#image-upload",
            preview_box: "#image-preview",
            label_field: "#image-label",
            label_default: "{{ __('Choose Icon') }}",
            label_selected: "{{ __('Change Icon') }}",
            no_label: false,
            success_callback: null
        });
    </script>
    <script>
        "use strict"

        function deleteData(id) {
            $("#deleteForm").attr("action", '{{ url('/doctor/social-link/') }}' + "/" + id);
        }
        "use strict"
        $('.itemModalClose').on('click', function() {
            $("#ItemForm").attr("action", "{{ url('/doctor/social-link') }}");
            $("#itemModal .modal-title").text("{{ __('Add Social Link') }}");
            $(".submit-btn").text("{{ __('Save') }}");
            $("#image-preview").css('background-image', 'none');
            $(".image-field label span").removeClass('d-none');
            $("#itemLink").val('');
            $('#itemModal').modal('hide');
        });

        "use strict"

        function editItemData(id, icon, link) {
            console.log('Hello')
            $("#ItemForm").attr("action", "{{ url('/doctor/social-link') }}" + '/' + id);
            $("#itemModal .modal-title").text("{{ __('Update Social Link') }}");
            $(".submit-btn").text("{{ __('Update') }}");
            $(".image-field label span").addClass('d-none');
            $("#image-preview").css({
                'background-image': 'url(' + icon + ')',
                'background-size': 'contain',
                'background-position': 'center center'
            });
            $("#itemLink").val(link);
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
                url: "{{ url('/doctor/social-link/status-update') }}" + "/" + id,
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
