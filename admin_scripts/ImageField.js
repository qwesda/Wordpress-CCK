(function($) {
    jQuery(document).ready(function (){
        jQuery('body').on("click", 'input.wpc_input_image_select', function(){
            event.preventDefault();

            tb_show('', 'media-upload.php?post_id='+postID+'&TB_iframe=true');
        });

        jQuery('body').on("click", 'input.wpc_input_image_remove', function(){
            var win = window.dialogArguments || opener || parent || top;

            event.preventDefault();

            var data = jQuery(this).data();

            var args = {
                action:'set-' + data.image_field_key,
                post_id: win.postID,
                image_id: -1,
                _ajax_nonce: data.nonce

            };

            link = jQuery('.wpc_input_image_remove_' + data.image_field_key);

            link.val( "removing ..." );

            jQuery.post(ajaxurl, args, function(html){
                var win = window.dialogArguments || opener || parent || top;

                if ( html == '0' ) {
                    alert( "failed to removing field " + data.image_field_key);
                } else {
                    link.show();
                    link.val( "removed" );
                    link.fadeOut( 500, function() {
                        link.hide();
                    });

                    win.WPCImageFieldSetContainer(data.image_field_key, html);
                }
            }
            );
        });
    });

})(jQuery);

function WPCImageFiedSet(image_field_key, post_id, image_id, nonce) {
    var win = window.dialogArguments || opener || parent || top;

    var link = jQuery('#' + image_field_key + "-" + image_id);

    var args = {
        action:'set-' + image_field_key,
        post_id: win.postID,
        image_id: image_id,
        _ajax_nonce: nonce

    };

    link.val( "saving" );

    jQuery.post(ajaxurl, args, function(html){
        var win = window.dialogArguments || opener || parent || top;

        link.val( "done" );

        if ( html == '0' ) {
            alert( "failed to set field " + image_field_key);
        } else {
            link.show();
            link.val( "done" );
            link.parents("tr").fadeOut( 500, function() {
                link.parents("tr").hide();
            });

            win.WPCImageFieldSetContainer(image_field_key, html);
        }
    }
    );
}

function WPCImageFieldSetContainer (image_field_key, html) {
    jQuery('#wpc_image_field_container_' + image_field_key).parent().html(html);
}
