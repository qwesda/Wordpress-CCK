(function($) {
    jQuery(document).ready(function (){
        jQuery('a.wpc_input_date_edit_link').click(function(event) {
            event.preventDefault();

            jQuery(this).parent().hide();
            jQuery(this).parent().next().show();
        });
        jQuery('a.wpc_input_date_edit_ok').click(function(event) {
            event.preventDefault();
            ok(this);
        });
        jQuery('.wpc_input_date_edit_container > input').keypress(function(event) {
            if(event.which == 13){
                event.preventDefault();
            }
        });
        jQuery('.wpc_input_date_edit_container > input').keyup(function(event) {
            if(event.which == 13){
                event.preventDefault();
                ok(this);
            }
        });
        jQuery('a.wpc_input_date_edit_cancel').click(function(event) {
            event.preventDefault();

            jQuery(this).parent().prev().show();
            jQuery(this).parent().hide();
        });
    });
})(jQuery);

// mysqldate is from mike boone
// http://boonedocks.net/mike/archives/157-Formatting-a-Javascript-Date-for-MySQL.html
function mysqldate(date1) {
    return date1.getFullYear() + '-' +
        (date1.getMonth() < 9 ? '0' : '') + (date1.getMonth()+1) + '-' +
        (date1.getDate() < 10 ? '0' : '') + date1.getDate();
}

function ok (context) {
    var id = jQuery(context).attr('id').replace(/[^\-]+\-/, "");

    // the following selectors do not seem to work...
    
	var d = parseInt(jQuery("#wpc_input_date_d-"+id).val());
    var m = parseInt(jQuery("#wpc_input_date_m-"+id).val());
    var Y = parseInt(jQuery("#wpc_input_date_y-"+id).val());
			
	m = (m < 10 ? '0' : '') + m;
	d = (d < 10 ? '0' : '') + d;
			
    jQuery("input[name='wpc_"+id+"']").val(Y+'-'+m+'-'+d);
    jQuery("#wpc_input_date_timestamp-"+id).html(Y+'-'+m+'-'+d);

    jQuery(context).parent().prev().show();
    jQuery(context).parent().hide();
}
