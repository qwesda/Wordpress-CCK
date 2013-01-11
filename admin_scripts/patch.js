var wpc_patch = wpc_patch || new function() {
  this.do_patch = function(id) {
    var ajaxargs = {'nonce': wpc_settings_nonce};
    if (id) {
      ajaxargs.action = 'wpc_patch';
      ajaxargs.id = id;
    } else {
      ajaxargs.action = 'wpc_patch_all';
    }

    jQuery.post(ajaxurl, ajaxargs, function(resp_str) {
      var resp = jQuery.parseJSON(resp_str);

      jQuery('#wpc_patch_log_div').show();
      if (resp.errors.length > 0) {
        for (var patch in resp.errors) {
          jQuery('#wpc_patch_log').append("<li>Patch "+resp.errors[patch]+" failed to apply.</li>");
        }
      } else {
          jQuery('#wpc_patch_log').append("<li>Successfully applied patch(es).</li>");
      }
    });
  };
};

jQuery(document).ready(function () {
    jQuery('#wpc_patch_all').click(function (event) {
        event.preventDefault();
        wpc_patch.do_patch();
    });
    jQuery('.wpc_patch').click(function (event) {
        event.preventDefault();

        var id = jQuery(this).attr('id').replace(/^wpc_patch_/,'');
        wpc_patch.do_patch(id);
    });
});
