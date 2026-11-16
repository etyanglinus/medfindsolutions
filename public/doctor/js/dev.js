(function($) {
    "use strict";
    $(document).ready(function() {
        // Function to initialize Select2 for newly added elements
        function initializeSelect2(container) {
            container.find('.form-select').not('.wsus-select').select2();
        }
        // Add new prescribe medicine input field
        $("#addMedicineRow").on('click', function() {
            var html = $("#hiddenPrescribeRow .delete-prescribe-row").html();
            var newRow = $('<div class="row mt-3 delete-prescribe-row">' + html + '</div>');
            initializeSelect2(newRow);
            $("#medicineRow").append(newRow);
        });

        // remove prescribe medicine input field row
        $(document).on('click', '#removePrescribeRow', function() {
            $(this).closest('.delete-prescribe-row').remove();
        });
    });
})(jQuery);