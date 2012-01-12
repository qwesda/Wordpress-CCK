(function($) {
    $(document).ready(function (){
        $('a.wpc_input_date_edit_link').click(function(event) {
            event.preventDefault();
            $(this).parent().hide();
            $(this).parent().next().show();
        });
        $('a.wpc_input_date_edit_ok').click(function(event) {
            event.preventDefault();
            // the following selectors do not seem to work...
            var id = $(this).attr('id').substring('wpc_input_date_edit_ok-'.length);
            var d = $("#wpc_input_date_d-"+id).val();
            var m = $("#wpc_input_date_m-"+id).val();
            var Y = $("#wpc_input_date_y-"+id).val();
            $("input[name='wpc_"+id+"']").val(Y+'-'+m+'-'+d);
            $("#wpc_input_date_timestamp-"+id).html(Y+'-'+m+'-'+d);
            $(this).parent().prev().show();
            $(this).parent().hide();
        });
        $('a.wpc_input_date_edit_cancel').click(function(event) {
            event.preventDefault();
            $(this).parent().prev().show();
            $(this).parent().hide();
        });
    });
})(jQuery);
