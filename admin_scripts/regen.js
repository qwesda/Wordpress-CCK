var wpc_regen = wpc_regen || new function() {
  var chunksize = 100;
  var needed_iterations = {};
  var abort = false;
  this.post_count;

  var update_progress = function(iteration) {
    jQuery('#wpc_regen_progressbar').val(1+99*iteration/needed_iterations);
  }

  var process_chunk = function(iteration, last_id, type) {
    if (abort) {
      jQuery('#wpc_regen_log').append('<li>Regenerating generated fields for type '+type+'.</li>');
      wpc_regen.reset_processing();
      return;
    }
    var ajaxargs = {
      'action': 'wpc_regen_fields',
      'limit': chunksize,
      'type': type,
      'nonce': wpc_regen_nonce,
      'last_id': last_id
    }

    jQuery.post(ajaxurl, ajaxargs, function(resp_str) {
      var resp = jQuery.parseJSON(resp_str);

      var i = resp.errors.length;
      if (i) {
        while(i--) {
          jQuery('#wpc_regen_log').append('<li>'+resp.errors[i]+'</li>');
        }
        wpc_regen.reset_processing();
        return;
      }

      last_processed_id = resp['last_id'];
      update_progress(iteration);

      if (last_processed_id >= last_id + chunksize) {
        process_chunk(iteration+1, last_processed_id, type);
      }
      else {
        var str = '<li>Successfully regenerated posts of type '+type+'.</li>';
        jQuery('#wpc_regen_log').append(str);
        wpc_regen.reset_processing();
      }
    });

  }
  this.reset_processing = function() {
    abort = false;
    jQuery('#wpc_regen_progressbar').fadeOut();
    jQuery('#wpc_regen').show();
    jQuery('#wpc_regen_stop').hide();
    jQuery('#wpc_regen_start').show();
  }

  this.start_to_regenerate = function(type) {
    ajaxargs = {
      'action': 'wpc_regen_post_count',
      'type': type,
      'nonce': wpc_regen_nonce
    }

    // get count of published ids
    jQuery.post(ajaxurl, ajaxargs, function(resp_str) {
      var resp = jQuery.parseJSON(resp_str);

      var i = resp.errors.length;
      if (i) {
        while (i--) {
          jQuery('#wpc_regen_log').append('<li>'+resp.errors[i]+'</li>');
        }
        return;
      }

      if (typeof(resp.post_count) !== 'undefined') {
        post_count = resp.post_count;
        needed_iterations = Math.ceil(post_count/chunksize);
      }

      jQuery('#wpc_regen').hide();
      jQuery('#wpc_regen_progressbar').removeAttr("value");
      jQuery('#wpc_regen_progressbar').show();
      jQuery('#wpc_regen_stop').show();
      jQuery('#wpc_regen_start').hide();
      jQuery('#wpc_regen_log_div').show();

      process_chunk (1, -1, type);
    });
  };

  this.abort_regenerating = function() {
    abort = true;

  };
};

jQuery(document).ready(function () {
  jQuery('#wpc_regen_start').click(function (event) {
    event.preventDefault();
    var type = jQuery('#wpc_regen_content_type').val();
    wpc_regen.start_to_regenerate(type);
  });
  jQuery('#wpc_regen_stop').click(function (event) {
    event.preventDefault();
    var type = jQuery('#wpc_regen_content_type').val();
    wpc_regen.abort_regenerating(type);
  });
});
