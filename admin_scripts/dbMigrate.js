var wpc_db_migrate = wpc_db_migrate || new function() {
  this.start_db_migrate = function(type) {
    ajaxargs = {
      'action': 'wpc_db_migrate',
      'nonce': wpc_settings_nonce
    }

    jQuery.post(ajaxurl, ajaxargs, function(resp_str) {
      var resp = jQuery.parseJSON(resp_str);

      jQuery('#wpc_db_migrate_start').removeAttr("disabled");
    });
  };
};

jQuery(document).ready(function () {
    jQuery('#wpc_db_migrate_start').click(function (event) {
        event.preventDefault();

        wpc_db_migrate.start_db_migrate();

        jQuery('#wpc_db_migrate_start').attr("disabled", "disabled");
    });
});
