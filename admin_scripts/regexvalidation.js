(function($) {
    $(document).ready(function (){
        $('span.wpc_regexval_display').click(function(event) {
            var id = $(this).attr('id').substring('wpc_regexval_display_'.length);
            $(this).hide();
            $('#wpc_input_regexval_'+id).val($('#wpc_regexval_display_'+id).text());
            $('#wpc_regexval_input_'+id).show();
            $('#wpc_input_regexval_'+id).focus();
        });
        $('.wpc_regexval_input > a.wpc_val_apply').click(function(event) {
            event.preventDefault();

            var id = $(this).parent().attr('id').substring('wpc_regexval_input_'.length);
            var candidate = $('#wpc_input_regexval_'+id).val();

            var matched = wpc_regexval[id].some(function (r) {
                var regex = r[0], replacedb=r[1], replaceui=r[2];
                if (regex.test(candidate)) {
                    $('#wpc_'+id).val(replacedb? candidate.replace(regex, replacedb): candidate);
                    $('#wpc_regexval_display_'+id).text(replaceui? candidate.replace(regex, replaceui): candidate);
                    return true;
                }
            });

            if (matched) {
                $(this).parent().hide();
                $('#wpc_regexval_display_'+id).show();
            } else {
                $(this).parent().effect('shake', {times:3}, 'fast', function() {
                    $('#wpc_input_regexval_'+id).focus();
                });
            }
        });
        $('.wpc_regexval_input > a.wpc_val_cancel').click(function(event) {
            event.preventDefault();
            var id = $(this).parent().attr('id').substring('wpc_regexval_input_'.length);
            $(this).parent().hide();
            $('#wpc_regexval_display_'+id).show();
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
