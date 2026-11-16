@extends('installer::app')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <p>Verify Purchase</p>
            <a class="btn btn-outline-primary @if (!session()->has('step-1-complete')) disabled @endif" href="{{ route('setup.requirements') }}">Next &raquo;</a>
        </div>
        <div class="card-body">
            <div class="mb-3 row">
                <div class="col-12">
                    @if (!strtolower(config('app.app_mode')))
                        @php(session()->put('step-1-complete', true))
                        <div class="p-1">
                            <p>
                                You are using demo mode. No purchase code needed. Continue installation.
                            </p>
                        </div>
                        <a href="{{ route('setup.requirements') }}" class="btn btn-success">Continue</a>
                    @else
						@php(session()->put('step-1-complete', true))
                        <form id="verify_form">
                            <label for="purchase_code">Purchase Code</label>
                            <input type="text" id="purchase_code" value="babiato-ca5c-49c2-83f6-696a738b0000" class="mb-2 form-control" readonly />
                            <button id="submit_btn" type="submit" class="btn btn-primary">
                                Check
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <p style="text-align:center; font-weight:bold;"></br>Nulled by J.C. from <a href="https://babia.to" target="_self">babiato</a></p>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('submit', '#verify_form', async function(e) {
                e.preventDefault();
                let code = $('#purchase_code').val();
                let submit_btn = $('#submit_btn');

                if ($.trim(code) === '') {
                    toastr.warning("Purchase Code is required");
                } else {
                    submit_btn.html(
                        'Checking... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                    ).prop('disabled', true);
                    try {
                        const res = await makeAjaxRequest({
                            purchase_code: code
                        }, "{{ route('setup.checkParchase') }}");
                        if (res.success) {
                            toastr.success(res.message);
                            submit_btn.addClass('btn-success').html('Redirecting...');
                            window.location.href = "{{ route('setup.requirements') }}";
                        } else {
                            $('#purchase_code').val('');
                            submit_btn.html('Check').prop('disabled', true);
                            toastr.success(res.message);
                            submit_btn.addClass('btn-success').html('Redirecting...');
                            window.location.href = "{{ route('setup.requirements') }}";
                        }
                    } catch (error) {
                        submit_btn.html('Check').prop('disabled', true);
                        $.each(error.errors, function(index, value) {
                            toastr.success(res.message);
                            submit_btn.addClass('btn-success').html('Redirecting...');
                            window.location.href = "{{ route('setup.requirements') }}";
                        });
                    }
                }
            })
        });
    </script>
@endpush