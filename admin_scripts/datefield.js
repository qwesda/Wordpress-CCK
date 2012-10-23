(function($) {
    jQuery(document).ready(function (){
        jQuery('a.wpc_input_date_edit_link').live("click", function(event) {
            var id = jQuery(this).parent().parent().attr('id').replace("wpc_form_field_id_", "");

            event.preventDefault();

            jQuery(this).parent().hide();
            jQuery(this).parent().next().show();

            jQuery("#wpc_input_date_d-"+id).focus();
        });
        jQuery('a.wpc_input_date_edit_ok').live("click", function(event) {
            event.preventDefault();
            ok(this);
        });
        jQuery('.wpc_input_date_edit_container > input, .wpc_input_date_edit_container > select').live("keypress", function(event) {
            if(event.which == 13){
                event.preventDefault();
            }
        });
        jQuery('.wpc_input_date_edit_container > input, .wpc_input_date_edit_container > select').live("keyup", function(event) {
            if(event.which == 13){
                event.preventDefault();
                ok(this);
            }
        });
        jQuery('a.wpc_input_date_edit_cancel').live("click", function(event) {
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

    var d = parseInt(jQuery(context).parent().find("#wpc_input_date_d-"+id).val());
    var m = parseInt(jQuery(context).parent().find("#wpc_input_date_m-"+id).val());
    var Y = parseInt(jQuery(context).parent().find("#wpc_input_date_y-"+id).val());

    m = (m < 10 ? '0' : '') + m;
    d = (d < 10 ? '0' : '') + d;

    proposedDate = Y+'-'+m+'-'+d;

    if ( !isNaN( Date.parse(proposedDate) ) && !isNaN(d) && !isNaN(m) && !isNaN(Y) ) {
        jQuery(context).parent().parent().find(".wpc_input_date").val(proposedDate);
        jQuery(context).parent().parent().find("span").last().html(proposedDate);

        jQuery(context).parent().prev().show();
        jQuery(context).parent().hide();

        jQuery(context).parent().parent().find("input").removeClass('validationError');
    } else {
        jQuery(context).parent().parent().find("input").addClass('validationError');
    }
}
