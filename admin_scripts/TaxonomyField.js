jQuery(document).ready(function (){

    jQuery('body').on('change', "div.wpc_field_taxonomy_selector :checkbox", function(event){
        event.preventDefault();

        var selector       = jQuery(this).parents(".wpc_field_taxonomy_selector");
        var checked        = selector.find(':checked');
        var values         = checked.map(function (i, m) { return jQuery(m).val(); } );
        var field_input    = selector.find('.wpc_field_taxonomy_selector_value');

        values = values.toArray().join();

        field_input.val(values);

        console.log(field_input.val());
    });
});
