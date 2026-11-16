(function($) {
    "use strict";
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // load available appointment in doctor details
    $(document).on('change', '#datepicker-value', function() {
        var value = $(this).val();
        var doctor_id = $("#doctor_id").val();
        var url = base_url + "/get-appointment";
        $.ajax({
            type: 'GET',
            url: url,
            data: {
                'doctor_id': doctor_id,
                'date': value
            },
            success: function(response) {
                if (response.success) {
                    $("#doctor-available-schedule").html(response.success)
                    $("#schedule-box-outer").removeClass('d-none')
                    $("#doctor-schedule-error").addClass('d-none')
                    $("#sub").prop("disabled", false);
                }
                if (response.error) {
                    $("#schedule-box-outer").addClass('d-none')
                    $("#doctor-schedule-error").removeClass('d-none')
                    $("#doctor-schedule-error").html(response.error)
                    $("#sub").prop("disabled", true);
                }

            },
            error: function(err) {}
        });
    });

    //get patient message
    $(document).ready(function() {
        $('.user').on('click', function() {
            $('.user').removeClass('active');
            $(this).addClass('active');
            $(this).find('.pending').addClass('d-none');

            receiver_id = $(this).attr('id');
            $.ajax({
                type: "get",
                url: base_url + "/patient/get-message/" + receiver_id,
                data: "",
                cache: false,
                success: function(data) {
                    $('#messages').html(data.view);
                    if(!data.unseen_message){
                        $('.beep').removeClass('parent');
                    }
                    scrollToBottomFunc();
                }
            });
        });

        $(document).on('keyup', '.input-text input', function(e) {
            var message = sanitizeInput($(this).val());

            if (message != '') {
                $("#sentMessageBtn").prop("disabled", false);
            } else {
                $("#sentMessageBtn").prop("disabled", true);
            }

            if (e.keyCode == 13 && message != '' && receiver_id != '') {
                $(this).val('');

                var data = {
                    receiver_id: receiver_id,
                    message: message
                };

                sentMessageItemAppend(`#user-${my_id}`,message);

                // project demo mode check
                if (isDemo == 'DEMO') {
                    toastr.error(demo_mode_error);
                    return;
                }
                // end

                $.ajax({
                    type: "POST",
                    url: base_url + "/patient/send-message",
                    data: data,
                    cache: false,
                    success: function(data) {
                        scrollToBottomFunc();
                        $('#' + data.doctor_id).click();

                    },
                    error: function(jqXHR, status, err) {}
                })
            }
        });

    });


    // load available appointment in appoinment model
    $(document).on('change', '#modal-datepicker-value', function() {
        var value = $(this).val();
        var doctorId = $("#modal_doctor_id").val();
        var url = base_url + "/get-appointment";
        $.ajax({
            type: 'GET',
            url: url,
            data: {
                'doctor_id': doctorId,
                'date': value
            },
            success: function(response) {
                if (response.success) {
                    $("#available-modal-schedule").html(response.success)
                    $("#modal-schedule-box").removeClass('d-none')
                    $("#modal-schedule-error").addClass('d-none')
                    $("#modal-sub").prop("disabled", false);
                }
                if (response.error) {
                    $("#modal-schedule-box").addClass('d-none')
                    $("#modal-schedule-error").removeClass('d-none')
                    $("#modal-schedule-error").html(response.error)
                    $("#modal-sub").prop("disabled", true);


                }
            },
            error: function(err) {}
        });
    });

    // load available appointment in appoinment model
    $(document).on('change', '#mobile-modal-datepicker-value', function() {
        var value = $(this).val();
        var doctorId = $("#mobile_modal_doctor_id").val();
        var url = base_url + "/get-appointment";
        $.ajax({
            type: 'GET',
            url: url,
            data: {
                'doctor_id': doctorId,
                'date': value
            },
            success: function(response) {
                if (response.success) {
                    $("#available-mobile-modal-schedule").html(response.success)
                    $("#mobile-modal-schedule-box").removeClass('d-none')
                    $("#mobile-modal-schedule-error").addClass('d-none')
                    $("#mobile-modal-sub").prop("disabled", false);
                }
                if (response.error) {
                    $("#mobile-modal-schedule-box").addClass('d-none')
                    $("#mobile-modal-schedule-error").removeClass('d-none')
                    $("#mobile-modal-schedule-error").html(response.error)
                    $("#mobile-modal-sub").prop("disabled", true);
                }
            },
            error: function(err) {}
        });
    });

    $(document).on('click', '#zoom_demo_mode', function() {
        toastr.error(demo_mode_error);
    });
})(jQuery);

function sanitizeInput(input) {
    return input.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

// send messag by click button
function sendMessage() {
    var message = sanitizeInput($(".submit").val());
    $(".submit").val('');

    var data = {
        receiver_id: receiver_id,
        message: message
    };
    sentMessageItemAppend(`#user-${my_id}`,message);

    // project demo mode check
    if (isDemo == 'DEMO') {
        toastr.error(demo_mode_error);
        return;
    }
    // end

    $.ajax({
        type: "POST",
        url: base_url + "/patient/send-message",
        data: data,
        cache: false,
        success: function(data) {
            scrollToBottomFunc();
            $('#' + data.doctor_id).click();
        },
        error: function(jqXHR, status, err) {}
    })
}

// load doctor in modal
function loadDoctor() {
    var id = $(".department-id").val();
    if (id) {
        var url = base_url + "/get-department-doctor/" + id;
        $.ajax({
            type: 'GET',
            url: url,
            success: function(response) {
                $(".doctor-id").html(response).select2({
                    dropdownParent: $('#appointment_modal')
                });
                $("#modal-doctor-box").removeClass('d-none')
            },
            error: function(err) {}
        });

    }
}
// load doctor in mobile menu modal
function loadMobileModalDoctor() {
    var id = $(".modal-department-id").val();
    if (id) {
        var url = base_url + "/get-department-doctor/" + id;
        $.ajax({
            type: 'GET',
            url: url,
            success: function(response) {
                $(".modal-doctor-id").html(response)
                $("#mobile-modal-doctor-box").removeClass('d-none')
            },
            error: function(err) {}
        });

    }
}

// load date in modal
function loadDate() {
    var doctorId = $('.doctor-id').val()
    $('#modal_doctor_id').val(doctorId)
    $("#modal-date-box").removeClass('d-none')

}

// load date in mobile menu modal
function loadModalDate() {
    var doctorId = $('.modal-doctor-id').val()
    $('#mobile_modal_doctor_id').val(doctorId)
    $("#mobile-modal-date-box").removeClass('d-none')

}
function sentMessageItemAppend(boxId,message){
    scrollToBottomFunc();
    const messageList = $(boxId);
    const messageItem = `<li class="message clearfix">
            <div class="sent">
                <p>${message}</p>
                <p class="date"><i class="fas fa-spinner fa-spin"></i></p>
            </div>
        </li>`;
    messageList.append(messageItem);
}