(function($) {
    jQuery(document).ready(function (){
        jQuery('body').on('input.wpc_input_file_select', "click", function(){
            event.preventDefault();

            tb_show('', 'media-upload.php?post_id='+postID+'&TB_iframe=true');
        });
    });

    jQuery(document).ready(function (){
        jQuery('body').on('input.wpc_input_file_remove', "click", function(){
            var win = window.dialogArguments || opener || parent || top;

            event.preventDefault();

            var data = jQuery(this).data();

            var args = {
                action:'set-' + data.file_field_key,
                post_id: win.postID,
                file_id: -1,
                _ajax_nonce: data.nonce

            };

            link = jQuery('.wpc_input_file_remove_' + data.file_field_key);

            link.val( "removing ..." );

            jQuery.post(ajaxurl, args, function(html){
                var win = window.dialogArguments || opener || parent || top;

                if ( html == '0' ) {
                    alert( "failed to removing field " + data.file_field_key);
                } else {
                    link.show();
                    link.val( "removed" );
                    link.fadeOut( 500, function() {
                        link.hide();
                    });

                    WPCFileFieldSetContainer(data.file_field_key, html);
                }
            }
            );
        });
    });

})(jQuery);

function WPCFileFiedSet(file_field_key, post_id, file_id, nonce) {
    var win = window.dialogArguments || opener || parent || top;

    var link = jQuery('#' + file_field_key + "-" + file_id);

    var args = {
        action:'set-' + file_field_key,
        post_id: win.postID,
        file_id: file_id,
        _ajax_nonce: nonce

    };

    link.val( "saving" );

    jQuery.post(ajaxurl, args, function(html){
        var win = window.dialogArguments || opener || parent || top;

        link.val( "done" );

        if ( html == '0' ) {
            alert( "failed to set field " + file_field_key);
        } else {
            link.show();
            link.val( "done" );
            link.parents("tr").fadeOut( 500, function() {
                link.parents("tr").hide();
            });

            win.WPCFileFieldSetContainer(file_field_key, html);
        }
    }
    );
}

function WPCFileFieldSetPreview (file_field_key, html) {
    jQuery('#wpc_file_field_preview_' + file_field_key).html(html);
}

function WPCFileFieldSetContainer (file_field_key, html) {
    jQuery('#wpc_file_field_container_' + file_field_key).html(html);
}
