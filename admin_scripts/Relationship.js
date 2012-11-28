function goto_box (relation_data, to_box_id, from_box_id, direction) {
    var base_id = ".relation_edit_box." + relation_data.relId + " ";


    switch (from_box_id) {
        case "relation_add_search_box":
            if (to_box_id != "relation_connect_existing_box") {
                jQuery(base_id + '.relation_src_search').val('');
                jQuery(base_id + '.relation_src_list tbody').empty();
            }
            break;
        case "relation_connect_existing_box" :
            jQuery(base_id + '.relation_connect_existing_metadata_box').empty();
            break;
        case "relation_edit_connected_box" :
            break;
        case "relation_connect_new_box" :
            jQuery(base_id + '.new_item_title').val('');
            jQuery(base_id + '.relation_connect_new_metadata_box').empty();
            break;
    }

    if (from_box_id == "relation_connected_box" && to_box_id == "relation_edit_connected_box") {

    } else if (from_box_id == "relation_add_search_box" && to_box_id == "relation_connect_existing_box") {

    } else {
        jQuery(base_id + ' > div:visible').hide();
        jQuery(base_id + '.' + to_box_id).show();
    }

    switch (to_box_id) {
        case "relation_add_search_box":
            jQuery(base_id + '.relation_src_search').val('');
            update_search_results(relation_data);
            break;
        case "relation_connected_box" :
            set_connected_items(relation_data);
            break;
        case "relation_connect_new_box" :
            var editBox                 = htmlspecialchars_decode(relation_data.editBox);
            var itemEditBox             = htmlspecialchars_decode(relation_data.itemNewEditBox);

            var info_text               =
              "<h3>Add new " + relation_data.dstSingularLabel + "</h3>" + "<div class='padding_box'>"
            + ( itemEditBox != ""   ? "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>Item Metadata</label>" + itemEditBox + "</div>" : "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>No Editable Item Metadata</label></div>")
            + ( editBox != ""       ? "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>Relation Metadata</label>" + editBox + "</div>" : "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>No Editable Relation Metadata</label></div>")
            + "</div>";

            jQuery(base_id + '.relation_connect_new_metadata_box').empty().append(info_text);

            jQuery(base_id + '.relation_connect_new_metadata_box').each(check_text_input_value);
            jQuery(base_id + '.relation_connect_new_metadata_box input').first().focus();

            move_wpc_labels_to_rows();

            try {
                on_relation_metabox_ready();
            } catch (err) {

            }

            relation_data_connect_new = relation_data;

            break;
        case "relation_connect_existing_box" :
            var selected_item           = jQuery(base_id + '.relation_src_list tbody tr.selected');
            var selected_item_data      = jQuery(selected_item).data();
            var selected_item_name      = jQuery(base_id + '.relation_src_list tbody tr.selected a').text();
            var object_id               = selected_item_data.postId;

            var editBox                 = htmlspecialchars_decode(relation_data.editBox);
            var itemEditBox             = htmlspecialchars_decode(relation_data.itemUpdateEditBox);

            var item_metabox            = jQuery(base_id + ".relation_connect_existing_box").clone(true);
//            item_metabox.show().appendTo(base_id + '.relation_src_list tr.selected > td:first-child');

            jQuery(base_id + ".relation_add_search_box th .relation_buttons_box").hide()
            item_metabox.appendTo(base_id + '.relation_src_list tr.selected > td:first-child');
            item_metabox.find(".relation_buttons_box").appendTo(base_id + ".relation_add_search_box th");
            item_metabox.show();

            var info_text               = "<div class='padding_box'>"
            + ( itemEditBox != "" ? "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>Item Metadata</label>" + itemEditBox + "</div>" : "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>No Editable Item Metadata</label></div>")
            + ( editBox != "" ? "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>Relation Metadata</label>" + editBox + "</div>" : "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>No Editable Relation Metadata</label></div>")
            + "</div>";

            var data = {
                    action  : "get_post_metadata",
                    nonce   : nonce_relations_ajax,
                    post_id : object_id
                };

            jQuery.ajax({
                url: ajaxurl,
                dataType: "json",
                data : data,
                cache : false,
                context : {
                    "selected_item" : selected_item,
                    "selected_item_data" : selected_item_data,
                    "relation_data" : relation_data,
                    "info_text" : info_text,
                    "data" : data,
                    "base_id" : base_id,
                    "object_id" : object_id,
                    "item_metabox" : item_metabox
                },
                success: function (data) {
                    var item_metadata = data.results;

                    item_metabox.find('.relation_connect_existing_metadata_box').empty().append(info_text);

                    jQuery("<table class='relation_metadata'>"
                    + "<tr><td class='table_label'>object id</td><td>" + object_id + " <a class='relation_edit_link' target='_blank' href='" + admin_url_post_php + "?post=" + object_id + "&action=edit'>edit "+relation_data.dstSingularLabel+"</a></td></tr>"
                    + "</table>").prependTo(base_id + '.relation_src_list tr.selected > td:first-child');

                    item_metabox.find(".relation_connect_existing_metadata_box .wpc_input_text").each(check_text_input_value);
                    item_metabox.find(".relation_connect_existing_metadata_box .wpc_input:first").focus();

                    for (var metadata_key in item_metadata){
                        var input_id    = '#wpc_'+relation_data.srcId+'_field_'+metadata_key;
                        var input_val   = item_metadata[metadata_key];
                        var input       = jQuery(input_id);

                        if ( !input.is(':checkbox'))    input.val(input_val);
                        else                            input.attr('checked', (input_val == true || input_val == 1 || input_val == "1" || input_val == "true" || input_val == 1) );
                    }

                    move_wpc_labels_to_rows();

                    try {
                        on_relation_metabox_ready();
                    } catch (err) {

                    }

                    relation_data_connect_existing = relation_data;
                }
            });
            break;
        case "relation_edit_connected_box" :
            var selected_item           = jQuery(base_id + '.relation_connected_list tr.selected');
            var selected_item_data      = jQuery(selected_item).data();
            var object_id               = (selected_item_data.data.post_from_id != relation_data.postId ? selected_item_data.data.post_from_id : selected_item_data.data.post_to_id);

            var editBox                 = htmlspecialchars_decode(relation_data.editBox);
            var itemEditBox             = htmlspecialchars_decode(relation_data.itemUpdateEditBox);

            var item_metabox            = jQuery(base_id + ".relation_edit_connected_box").clone(true);

            jQuery(base_id + ".relation_connected_box th .relation_buttons_box").hide()
            item_metabox.appendTo(base_id + '.relation_connected_list tr.selected > td:first-child');
            item_metabox.find(".relation_buttons_box").appendTo(base_id + ".relation_connected_box th");
            item_metabox.show();

            var info_text               = "<div class='padding_box'>"
            + ( itemEditBox != "" ? "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>Item Metadata</label>" + itemEditBox + "</div>" : "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>No Editable Item Metadata</label></div>")
            + ( editBox != "" ? "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>Relation Metadata</label>" + editBox + "</div>" : "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>No Editable Relation Metadata</label></div>")
            + "</div>";

            var data = {
                    action  : "get_post_metadata",
                    nonce   : nonce_relations_ajax,
                    post_id : object_id
                };

            jQuery.ajax({
                url: ajaxurl,
                dataType: "json",
                data : data,
                cache : false,
                context : {
                    "selected_item" : selected_item,
                    "selected_item_data" : selected_item_data,
                    "relation_data" : relation_data,
                    "info_text" : info_text,
                    "data" : data,
                    "base_id" : base_id,
                    "object_id" : object_id,
                    "item_metabox" : item_metabox
                },
                success: function (data) {
                    var item_metadata = data.results;

                    item_metabox.find('.relation_edit_connected_metadata_box').empty().append(info_text);

                    jQuery("<table class='relation_metadata'>"
                    + "<tr><td class='table_label'>relation id</td><td>" + selected_item_data.data.id + "</td></tr>"
                    + "<tr><td class='table_label'>object id</td><td>" + object_id + " <a class='relation_edit_link' target='_blank' href='" + admin_url_post_php + "?post=" + object_id + "&action=edit'>edit "+relation_data.dstSingularLabel+"</a></td></tr>"
                    + "</table>").prependTo(base_id + '.relation_connected_list tr.selected > td:first-child');

                    item_metabox.find(".relation_edit_connected_metadata_box .wpc_input_text").each(check_text_input_value);
                    item_metabox.find(".relation_edit_connected_metadata_box .wpc_input:first").focus();

                    for (var metadata_key in selected_item_data.data.relation_metadata){
                        var input_id    = '#wpc_'+relation_data.relId+'_field_'+metadata_key;
                        var input_val   = selected_item_data.data.relation_metadata[metadata_key];
                        var input       = jQuery(input_id);

                        if ( !input.is(':checkbox'))    input.val(input_val);
                        else                            input.attr('checked', (input_val == true || input_val == 1 || input_val == "1" || input_val == "true" || input_val == 1) );
                    }

                    for (var metadata_key in item_metadata){
                        var input_id    = '#wpc_'+relation_data.srcId+'_field_'+metadata_key;
                        var input_val   = item_metadata[metadata_key];
                        var input       = jQuery(input_id);

                        if ( !input.is(':checkbox'))    input.val(input_val);
                        else                            input.attr('checked', (input_val == true || input_val == 1 || input_val == "1" || input_val == "true" || input_val == 1) );
                    }

                    move_wpc_labels_to_rows();

                    try {
                        on_relation_metabox_ready();
                    } catch (err) {

                    }

                    relation_data_edit_connected = relation_data;

                }
            });
            break;
    }
}

function relation_add_search_cancel (relation_data) {
    goto_box(relation_data, 'relation_connected_box', 'relation_add_search_box', 'back');
}
function add_selected_item (relation_data) {
    goto_box(relation_data, 'relation_connect_existing_box', 'relation_add_search_box', 'forward');
}
function relation_edit_connected_cancel (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    jQuery(base_id + '.relation_connected_list > tbody > tr > td > .relation_metadata').remove();
    jQuery(base_id + '.relation_connected_list > tbody > tr > td > .relation_edit_connected_box').remove();

    jQuery(base_id + '.relation_connected_list > tbody > tr.selected').removeClass('selected');

    jQuery(base_id + ".relation_connected_box th .relation_edit_connected_buttons_box").remove();
    jQuery(base_id + ".relation_connected_box th .relation_buttons_box").show();
}
function relation_edit_connected_update (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    var selected_item       = jQuery(base_id + '.relation_connected_list > tbody > tr.selected');
    var selected_item_data  = jQuery(base_id + '.relation_connected_list > tbody > tr.selected').data();
    var selected_item_label = jQuery(base_id + '.relation_connected_list > tbody > tr.selected > td > a.relation_connected_item').text();
    var object_id           = (selected_item_data.data.post_from_id != relation_data.postId ? selected_item_data.data.post_from_id : selected_item_data.data.post_to_id);
    var object_type         = (selected_item_data.data.post_from_id != relation_data.postId ? relation_data.dstId : relation_data.srcId);

    var relation_metadata_fields = jQuery(base_id + '.relation_edit_connected_metadata_box .relation_metadata_edit_box .wpc_input');
    var item_metadata_fields     = jQuery(base_id + '.relation_edit_connected_metadata_box .relation_item_metadata_edit_box .wpc_input');

    var data = {
            action              : "update_relation",
            nonce               : nonce_relations_ajax,
            rel_id              : relation_data.relId,
            id                  : selected_item_data.data.id,
            from_id             : selected_item_data.data.post_from_id,
            to_id               : selected_item_data.data.post_to_id,
            item_id             : object_id,
            item_type           : object_type,
            relation_metadata   : {},
            item_metadata       : {}
        };

    data.relation_metadata  = getInputData(relation_data, relation_metadata_fields);
    data.item_metadata      = getInputData(relation_data, item_metadata_fields);

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        context : {
            "selected_item"         : selected_item,
            "selected_item_label"   : selected_item_label,
            "selected_item_data"    : selected_item_data,
            "data"                  : data
        },
        success: function (data) {
            show_status_message(relation_data, this.selected_item_label + " updated");
            goto_box(relation_data, 'relation_connected_box', 'relation_edit_connected', 'back');

            jQuery(base_id + ".relation_connected_box th .relation_edit_connected_buttons_box").remove();
            jQuery(base_id + ".relation_connected_box th .relation_buttons_box").show();
        }
    });
}
function relation_edit_connected_delete (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";
    var selected_item       = jQuery(base_id + '.relation_connected_list > tbody > tr.selected');
    var selected_item_data  = jQuery(base_id + '.relation_connected_list > tbody > tr.selected').data();
    var selected_item_label = jQuery(base_id + '.relation_connected_list > tbody > tr.selected > td > a.relation_connected_item').text();

    var metadata_fields     = jQuery(base_id + '.relation_edit_connected_metadata_box .wpc_input');

    var data = {
            action : "delete_relation",
            nonce  : nonce_relations_ajax,
            rel_id : relation_data.relId,
            id     : selected_item_data.data.id
        };

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        context : {
            "selected_item" : selected_item,
            "selected_item_label" : selected_item_label,
            "selected_item_data" : selected_item_data,
            "data" : data
        },
        success: function (data) {
            goto_box(relation_data, 'relation_connected_box', 'relation_edit_connected', 'back');

            show_status_message(relation_data, this.selected_item_label + " removed");


            jQuery(base_id + ".relation_connected_box th .relation_edit_connected_buttons_box").remove();
            jQuery(base_id + ".relation_connected_box th .relation_buttons_box").show();
        }
    });
}
function show_status_message(relation_data, message) {
    //jQuery(".relation_edit_box." + relation_data.relId + " .status-update").text(message).show().delay(200000).fadeOut();
}
function relation_connect_existing_add (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    var selected_item       = jQuery(base_id + '.relation_src_list > tbody > tr.selected');
    var selected_item_data  = jQuery(base_id + '.relation_src_list > tbody > tr.selected').data();
    var selected_item_label = jQuery(base_id + '.relation_src_list > tbody > tr.selected > td > a.relation_connected_item').text();
    var object_id           = selected_item_data.postId;
    var object_type         = selected_item_data.postType;

    var relation_metadata_fields = jQuery(base_id + '.relation_connect_existing_metadata_box .relation_metadata_edit_box .wpc_input');
    var item_metadata_fields     = jQuery(base_id + '.relation_connect_existing_metadata_box .relation_item_metadata_edit_box .wpc_input');

    var data = {
            action              : "add_relation",
            nonce               : nonce_relations_ajax,
            rel_id              : relation_data.relId,
            from_id             : relation_data.relDir == "to_from" ? relation_data.postId : selected_item.data('post-id'),
            to_id               : relation_data.relDir == "to_from" ? selected_item.data('post-id') : relation_data.postId,
            item_id             : object_id,
            item_type           : object_type,
            relation_metadata   : {},
            item_metadata       : {}
        };

    data.relation_metadata  = getInputData(relation_data, relation_metadata_fields);
    data.item_metadata      = getInputData(relation_data, item_metadata_fields);

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        context : {
            "selected_item"         : selected_item,
            "selected_item_label"   : selected_item_label,
            "selected_item_data"    : selected_item_data,
            "data"                  : data
        },
        success: function (data) {
            show_status_message(relation_data, this.selected_item_label + " added");

            jQuery(base_id + '.relation_src_list > tbody > tr > td > .relation_metadata').remove();
            jQuery(base_id + '.relation_src_list > tbody > tr > td > .relation_connect_existing_box').remove();
            jQuery(base_id + '.relation_src_list > tbody > tr.selected').removeClass('selected');

            jQuery(base_id + '.relation_src_search').focus();

            jQuery(base_id + ".relation_add_search_box th .relation_connect_existing_buttons_box").remove();
            jQuery(base_id + ".relation_add_search_box th .relation_buttons_box").show();

            goto_box(relation_data, 'relation_connected_box', 'relation_edit_connected', 'back');
        }
    });
}
function relation_connect_existing_cancel (relation_data) {
    var base_id = ".relation_edit_box." + relation_data.relId + " ";

    jQuery(base_id + '.relation_src_list > tbody > tr > td > .relation_metadata').remove();
    jQuery(base_id + '.relation_src_list > tbody > tr > td > .relation_connect_existing_box').remove();
    jQuery(base_id + '.relation_src_list > tbody > tr.selected').removeClass('selected');

    jQuery(base_id + '.relation_src_search').focus();

    jQuery(base_id + ".relation_add_search_box th .relation_connect_existing_buttons_box").remove();
    jQuery(base_id + ".relation_add_search_box th .relation_buttons_box").show();
}
function update_search_results (relation_data) {
    var base_id     = ".relation_edit_box." + relation_data.relId + " ";
    var search_box  = jQuery(base_id + '.relation_src_search');

    var filter_value        = search_box.val();
    var last_filter_value   = jQuery.data(search_box[0], 'last_filter_value');
    jQuery.data(search_box[0], 'last_filter_value', filter_value);

    if (filter_value != last_filter_value) {
        var header_html = "<img src='" + admin_url_wpspin_light + "'> searching for "+relation_data.dstLabel+" like <i>"+filter_value+"</i>";

        jQuery(base_id + '.relation_src_list > thead > tr > th').html(header_html);
        jQuery(base_id + '.relation_src_list tbody').empty();

        jQuery.ajax({
            url: ajaxurl,
            dataType: "json",
            cache : false,
            data : {
                action          : "get_post_type_items",
                nonce           : nonce_relations_ajax,
                post_type       : relation_data.srcId,
                relation_data   : relation_data.dstId,
                filter          : filter_value,
                offset          : 0,
                cache           : false,
                limit           : 100,
                order           : "asc",
                order_by        : "title"
            },
            success: function (data) {
                var html_to_append = "";

                for (var i = data.results.length - 1; i >= 0; i--) {
                    var result = data.results[i];


                    if (result.post_title == "") {
                        result.post_title  = "<i>untitled</i>";
                    }

                    html_to_append = "<tr data-post-id='"+result.ID+"' data-post-type='"+result.post_type+"'><td><a href='#' class='relation_source_item'>"+result.post_title+"</a>"
                    + (result.post_status != "publish" ? " (<i>" + result.post_status + ")</i>" : "")
                    + "</td></tr>\n" + html_to_append;
                }

                if (data.results.length == 0) {
                    html_to_append = "<tr class='relations_info'><td>no "+relation_data.dstLabel+" like \""+filter_value+"\" found</td></tr>";
                };

                var status_text = "";

                switch (data.status.result_type) {
                    case "available"    : status_text = "Available " + relation_data.dstLabel; break;
                    case "latest"       : status_text = "Latest " + relation_data.dstLabel + " (" + data.status.returned_results + " of " + data.status.available_results + ")"; break;
                    case "search"       : status_text = "Search results " + data.status.returned_results + " of " + data.status.available_results; break;

                }

                jQuery(base_id + '.relation_src_list > tbody').empty().append(html_to_append);
                jQuery(base_id + '.relation_src_list > thead > tr > th').text(status_text);
            }
        });
    }
}
function set_connected_items (relation_data) {
    var data = {
            action         : "get_connected_items",
            rel_id         : relation_data.relId
        };

    var base_id = ".relation_edit_box." + relation_data.relId + " ";

    if (relation_data.relDir == "from_to") {
        data.to_id = relation_data.postId;
    } else {
        data.from_id = relation_data.postId;
    }

    var html_to_append = "<div class='relations_info'><img src='" + admin_url_wpspin_light + "'> loading connected "+relation_data.dstLabel+"<br></div>";
    jQuery(base_id + '.relation_connected_list tbody').empty().append(html_to_append);

    jQuery.ajax({
        url      : ajaxurl,
        dataType : "json",
        data     : data,
        cache    : false,
        success  : function (ret) {
            var html_to_append = "";

            for (var i = ret.results.length - 1; i >= 0; i--) {
                var result = ret.results[i];

                if (result.item_metadata.post_title == "") {
                    result.item_metadata.post_title  = "<i>untitled</i>";
                }

                var fieldsToShowInListString    = "";
                var fieldsToShowInListValues    = [];

                if (relation_data.fieldsToShowInList != "" && relation_data.fieldsToShowInList != undefined) {
                    var fieldsToShowInList          = relation_data.fieldsToShowInList.split(",");

                     for (var j=0; j < fieldsToShowInList.length; j++) {
                        var field = fieldsToShowInList[j];

                        if (field.substr(0,11) != "other-item-") {
                            if (result.relation_metadata[field] != undefined && result.relation_metadata[field] != "") {
                                fieldsToShowInListValues.push( result.relation_metadata[field] );
                            }
                        } else {
                            field = field.substr(11);
                            if (result.item_metadata[field] != undefined && result.item_metadata[field] != "") {
                                fieldsToShowInListValues.push( result.item_metadata[field] );
                            }
                        }
                    }

                    fieldsToShowInListString = fieldsToShowInListValues.join(", ");
                }


                var lockRelation    = false;

                if (relation_data.fieldToLockRelation != "" && relation_data.fieldToLockRelation != undefined) {
                    var fieldToLockRelation          = relation_data.fieldToLockRelation;

                    if (result.relation_metadata[fieldToLockRelation] != undefined && result.relation_metadata[fieldToLockRelation] != "") {
                        var val = result.relation_metadata[fieldToLockRelation];

                        if ( val != undefined && (
                               val != ""
                            && val != "0"
                            && val != "false"
                            && val != 0
                            && val != false
                        ) ) {
                            lockRelation = true;
                        }
                    }
                }


                var fieldsToPutAsClassString    = "";
                var fieldsToPutAsClassValues    = [];

                if (relation_data.fieldsToPutAsClass != "" && relation_data.fieldsToPutAsClass != undefined) {
                    var fieldsToPutAsClass          = relation_data.fieldsToPutAsClass.split(",");

                     for (var j=0; j < fieldsToPutAsClass.length; j++) {
                        var field = fieldsToPutAsClass[j];

                        if (result.relation_metadata[field] != undefined && result.relation_metadata[field] != "") {
                            fieldsToPutAsClassValues.push( field + "_" + result.relation_metadata[field] );
                        }
                    }

                    fieldsToPutAsClassString = fieldsToPutAsClassValues.join(", ");
                }

                html_to_append =
                '<tr data-id="'+result.id+'" data-data="'+htmlspecialchars( json_encode(result), 3)+'" class="' + fieldsToPutAsClassString + '"><td>'
                + ( !lockRelation ?
                    "<a href='#' class='relation_connected_item'>"+result.item_metadata.post_title+"</a> " :
                    "<span class='relation_connected_item'>"+result.item_metadata.post_title+"</span> "
                )
                +    (result.item_metadata.post_status != "publish" ? " (<i>" + result.item_metadata.post_status + ")</i>" : "")
                +    (fieldsToShowInListString != "" ? "<div class='connected_item_info'>"+fieldsToShowInListString+"</div>" : "")
                + "</td></tr>\n" + html_to_append;
            }

            var relation_tab = jQuery("a[href='#tab_rel_" + relation_data.relId + "']");
            var text = relation_tab.text();
            text = text.replace(/ \([0-9]+\)$/, "");

            if (ret.results.length == 0) {
                html_to_append = "<div class='relations_info'>no "+relation_data.dstLabel+" connected yet<br>click on \"add connection\" to get started</div>";
            } else {
                text = text + " ("+ret.results.length+")";
            }

            relation_tab.text(text);

            jQuery(base_id + '.relation_connected_list tbody').empty().append(html_to_append);
        }
    });
}


function getInputData (relation_data, input_elements) {
    var data = {};

    for (var i=0; i < input_elements.length; i++) {
        var input_element = jQuery(input_elements[i]);

        data_key = input_element.attr('id').replace(relation_data.relId+"_", "").replace(relation_data.srcId+"_", "").replace("wpc_field_", "");

        if( !input_element.is(':checkbox') )    data[data_key] = htmlspecialchars( input_element.val(), 3);
        else                                    data[data_key] = input_element.is(':checked') ? 1 : 0;
    };

    return data;
}

function relation_connect_new_add (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    var relation_metadata_fields = jQuery(base_id + '.relation_connect_new_metadata_box .relation_metadata_edit_box .wpc_input');
    var item_metadata_fields     = jQuery(base_id + '.relation_connect_new_metadata_box .relation_item_metadata_edit_box .wpc_input');

    var data = {
            action              : "add_relation",
            nonce               : nonce_relations_ajax,
            rel_id              : relation_data.relId,
            relation_metadata   : {},
            item_metadata       : {}
        };

    if (relation_data.relDir == "to_from") {
        data.from_id = relation_data.postId;
    } else {
        data.to_id = relation_data.postId;
    }

    data.relation_metadata  = getInputData(relation_data, relation_metadata_fields);
    data.item_metadata      = getInputData(relation_data, item_metadata_fields);

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        context : {
            "data"                  : data
        },
        success: function (data) {
            goto_box(relation_data, 'relation_connected_box', 'relation_connect_existing_box', 'back');
            show_status_message(relation_data, "new " + relation_data.srcLabel + " added");

            relation_data_connect_existing = relation_data_edit_connected = relation_data_connect_new = null;
        }
    });
}
function relation_connect_new_cancel (relation_data) {
    goto_box(relation_data, 'relation_connected_box', 'relation_connect_new_box', 'back');

    relation_data_connect_existing = relation_data_edit_connected = relation_data_connect_new = null;
}
function relation_init_metabox (relation_data) {
    goto_box(relation_data, 'relation_connected_box', '', '');
}
function relation_setup_delegates (relation_metabox_id) {
    var relation_metabox    = jQuery(relation_metabox_id);
    var relation_data       = relation_metabox.data();
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    relation_init_metabox(relation_data);

    jQuery(relation_metabox_id).delegate('a.relation_connected_item', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        jQuery(base_id + '.relation_connected_list > tbody > tr > td > .relation_metadata').remove();
        jQuery(base_id + '.relation_connected_list > tbody > tr > td > .relation_edit_connected_box').remove();
        jQuery(base_id + '.relation_connected_list > tbody > tr.selected').removeClass('selected');

        jQuery(this).closest("tr").addClass("selected");

        goto_box(relation_data, 'relation_edit_connected_box', 'relation_connected_box', 'forward');

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_connected_add', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        var search_box = jQuery(base_id + '.relation_src_search');
        jQuery.data(search_box[0], 'last_filter_value', 'NULL');

        var html_to_append = "<div class='relations_info'>Type to search ...<br></div>";
        jQuery(base_id + '.relation_src_list tbody').empty().append(html_to_append);

        goto_box(relation_data, 'relation_add_search_box', 'relation_connected_box', 'forward');

        return false;
    });
    jQuery(relation_metabox_id).delegate('.relation_src_search', 'keyup', function(event) {
        update_search_results(relation_data);
    });
    jQuery(relation_metabox_id).delegate('a.relation_add_search_cancel', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_add_search_cancel(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_source_item', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        jQuery(base_id + '.relation_src_list > tbody > tr.selected').removeClass('selected');

        jQuery(base_id + '.relation_src_list > tbody > tr > td > .relation_metadata').remove();
        jQuery(base_id + '.relation_src_list > tbody > tr > td > .relation_connect_existing_box').remove();

        jQuery(this).closest("tr").addClass("selected");

        add_selected_item (relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_connected_add_new', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        goto_box(relation_data, 'relation_connect_new_box', 'relation_connected_box', 'forward');

        return false;

    });
    jQuery(relation_metabox_id).delegate('a.relation_edit_connected_cancel', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_edit_connected_cancel(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_open_all_connected', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        var connected = jQuery(base_id + '.relation_connected_list li');
        connected.each(function(i, item) {
            var item_data = jQuery(item).data();
            var url = admin_url_post_php + "?post=" + item_data.data.post_to_id + "&action=edit";

            window.open(url, '_blank');
        });

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_edit_connected_update', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_edit_connected_update(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_edit_connected_delete', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_edit_connected_delete(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_connect_existing_cancel', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_connect_existing_cancel(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_connect_existing_add', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_connect_existing_add(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_connect_new_cancel', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_connect_new_cancel(relation_data);

        return false;
    });
    jQuery(relation_metabox_id).delegate('a.relation_connect_new_add', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        relation_connect_new_add(relation_data);

        return false;
    });
}

jQuery(document).mouseup(function (e) {
    var src          = jQuery(e.srcElement);
    var is_outside   = jQuery(src).closest("tr.selected, .relation_buttons_box, .relation_connect_new_metadata_box").length == 0;

    //console.log("is_outside: %s", is_outside);

    if ( is_outside && relation_data_connect_existing != null ) {
        relation_connect_existing_cancel(relation_data_connect_existing);

        relation_data_connect_existing = null;
    }

    if ( is_outside && relation_data_edit_connected != null ) {
        relation_edit_connected_cancel(relation_data_edit_connected);

        relation_data_edit_connected = null;
    }

    if ( is_outside && relation_data_connect_new != null ) {
        relation_connect_new_cancel(relation_data_connect_new);

        relation_data_connect_new = null;
    }
});

var relation_data_edit_connected    = null;
var relation_data_connect_existing  = null;
var relation_data_connect_new       = null;
