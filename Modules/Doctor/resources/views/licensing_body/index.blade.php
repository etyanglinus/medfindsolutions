@extends('admin.master_layout')
@section('title')
    <title>{{ __('Licensing Bodies') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Licensing Bodies') }}" :list="[
                __('Dashboard') => route('admin.dashboard'),
                __('Licensing Bodies') => '#',
            ]" />
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                @adminCan('licensingbody.store')
                                    <div>
                                        <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#itemModal"
                                            class="btn btn-primary"><i class="fa fa-plus"></i> {{ __('Add New') }}</a>
                                    </div>
                                @endadminCan
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('SN') }}</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($licensingBodies as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->name }}</td>
                                                    <td>{{ ucfirst($item->type) }}</td>
                                                    <td>
                                                        @adminCan('licensingbody.update')
                                                            <input onchange="changeStatus({{ $item->id }})" id="status_toggle" type="checkbox"
                                                                {{ $item->status ? 'checked' : '' }} data-toggle="toggle"
                                                                data-onlabel="{{ __('Active') }}" data-offlabel="{{ __('Inactive') }}"
                                                                data-onstyle="success" data-offstyle="danger">
                                                        @else
                                                            <span class="badge bg-{{ $item->status ? 'success' : 'secondary' }}">{{ $item->status ? __('Active') : __('Inactive') }}</span>
                                                        @endadminCan
                                                    </td>
                                                    <td>
                                                        @adminCan('licensingbody.update')
                                                            <a href="javascript:;"
                                                                onclick='editItemData({{ $item->id }}, @json($item->name), @json($item->type), {{ $item->status ? '1' : '0' }})'
                                                                class="btn btn-warning btn-sm"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                                        @endadminCan
                                                        @adminCan('licensingbody.delete')
                                                            <x-admin.delete-button :id="$item->id" onclick="deleteData" />
                                                        @endadminCan
                                                    </td>
                                                </tr>
                                            @empty
                                                <x-empty-table :name="__('Item')" route="admin.licensingbody.index" create="no" :message="__('No data found!')" colspan="5" />
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $licensingBodies->onEachSide(0)->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @if (checkAdminHasPermission('licensingbody.store') || checkAdminHasPermission('licensingbody.update'))
        <div tabindex="-1" role="dialog" id="itemModal" class="modal fade">
            <div class="modal-dialog" role="document">
                <form class="modal-content" id="ItemForm" action="{{ route('admin.licensingbody.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Add New Licensing Body') }}</h5>
                        <button type="button" class="btn-close itemModalClose" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <x-admin.form-input id="itemTitle" name="name" label="{{ __('Name') }}" placeholder="{{ __('Enter Name') }}" required="true" />
                        <x-admin.form-select name="type" id="type" label="{{ __('Type') }}" required="true">
                            <x-admin.select-option value="" text="{{ __('Select Type') }}" />
                            <x-admin.select-option value="doctor" text="{{ __('Doctor') }}" />
                            <x-admin.select-option value="facility" text="{{ __('Facility') }}" />
                        </x-admin.form-select>
                        <x-admin.form-select name="status" id="status" label="{{ __('Status') }}" required="true">
                            <x-admin.select-option value="1" text="{{ __('Active') }}" />
                            <x-admin.select-option value="0" text="{{ __('Inactive') }}" />
                        </x-admin.form-select>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <x-admin.button variant="danger" data-bs-dismiss="modal" class="itemModalClose" text="{{ __('Close') }}" />
                        <x-admin.button class="submit-btn" type="submit" text="{{ __('Save') }}" />
                    </div>
                </form>
            </div>
        </div>
    @endif
    @adminCan('licensingbody.delete')
        <x-admin.delete-modal />
    @endadminCan
@endsection
@push('js')
    <script>
        function deleteData(id) {
            $('#deleteForm').attr('action', '{{ url('/admin/licensing-body') }}' + '/' + id);
        }

        $('.itemModalClose').on('click', function() {
            $('#ItemForm').attr('action', '{{ url('/admin/licensing-body') }}');
            $('#itemModal .modal-title').text('{{ __('Add New Licensing Body') }}');
            $('.submit-btn').text('{{ __('Save') }}');
            $('#itemTitle').val('');
            $('#type').val('');
            $('#status').val('1');
            $('#itemModal').modal('hide');
        });

        function editItemData(id, name, type, status) {
            $('#ItemForm').attr('action', '{{ url('/admin/licensing-body') }}' + '/' + id);
            $('#itemModal .modal-title').text('{{ __('Edit Licensing Body') }}');
            $('.submit-btn').text('{{ __('Update') }}');
            $('#itemTitle').val(name);
            $('#type').val(type);
            $('#status').val(status ? '1' : '0');
            $('#itemModal').modal('show');
        }

        function changeStatus(id) {
            var isDemo = '{{ env('APP_MODE') ?? 'LIVE' }}';
            if (isDemo === 'DEMO') {
                toastr.error('{{ __('This Is Demo Version. You Can Not Change Anything') }}');
                return;
            }

            $.ajax({
                type: 'put',
                data: { _token: '{{ csrf_token() }}' },
                url: '{{ url('/admin/licensing-body/status-update') }}' + '/' + id,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    }
                },
                error: function(err) {
                    console.log(err);
                }
            });
        }
    </script>
@endpush
