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
			
			m = (m < 10 ? '0' : '') + m;
			d = (d < 10 ? '0' : '') + d;
			
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

// this is from mike boone
// http://boonedocks.net/mike/archives/157-Formatting-a-Javascript-Date-for-MySQL.html
function mysqldate(date1) {
  return date1.getFullYear() + '-' +
    (date1.getMonth() < 9 ? '0' : '') + (date1.getMonth()+1) + '-' +
    (date1.getDate() < 10 ? '0' : '') + date1.getDate();
}
