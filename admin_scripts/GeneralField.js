function move_wpc_labels_to_rows (argument) {
    jQuery.each(jQuery('div.wpc_form_field > label.wpc_helptext'), function() {
        var label   = jQuery(this);
        var row     = label.parent().parent();

        if ( row.hasClass("wpc_form_row") ) {
            label.appendTo(row).hide();
        }
    });
}

(function($) {
    jQuery(document).ready(function (){
        move_wpc_labels_to_rows();

        jQuery('body').on('focusin', ".wpc_input", function(event){
            event.preventDefault();

            jQuery('label.wpc_helptext').fadeOut(100);

            var parentID    = jQuery(this).attr('id');
            var label       = jQuery("label[for='"+parentID+"']");

            label.fadeIn(200);
        });
        jQuery('body').on('focusout', ".wpc_input", function(event){
            event.preventDefault();

            jQuery('label.wpc_helptext').hide();
        });
        /**/
    });

})(jQuery);
