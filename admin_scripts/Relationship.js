function show_box (relation_data, box_to_show_id) {
    jQuery(relation_data.metaboxSelector + ' > div:visible').hide();
    jQuery(relation_data.metaboxSelector + '.' + box_to_show_id).show();
}

function getInputData (relation_data, input_elements) {
    var data = {};

    for (var i=0; i < input_elements.length; i++) {
        var input_element = jQuery(input_elements[i]);

        var data_key = input_element.attr('id')
            .replace("wpc_" + relation_data.relIdClean+"_field_", "")
            .replace("wpc_" + relation_data.srcId+"_field_", "")
            .replace("wpc_" + relation_data.dstId+"_field_", "");

        if( !input_element.is(':checkbox') )    data[data_key] = htmlspecialchars( input_element.val(), 3);
        else                                    data[data_key] = input_element.is(':checked') ? 1 : 0;
    };

    return data;
}
function update_tab_label (relation_data, text) {
    var relation_tab = jQuery("a[href='#tab_rel_" + relation_data.relId + "']");
    relation_tab.text(text);
}
function update_search_results (relation_data) {
    var search_box          = jQuery(relation_data.metaboxSelector + '.relation_src_search');

    var filter_value        = search_box.val();
    last_filter_value       = jQuery.data(search_box[0], 'last_filter_value');

    jQuery.data(search_box[0], 'last_filter_value', filter_value);

    if (filter_value != last_filter_value) {
        var header_html = "<img src='" + admin_url_wpspin_light + "'> searching for "+relation_data.dstLabel+" like <i>"+filter_value+"</i>";

        jQuery(relation_data.metaboxSelector + '.relation_src_list > thead > tr > th').html(header_html);
        jQuery(relation_data.metaboxSelector + '.relation_src_list tbody').empty();

        jQuery.ajax({
            url: ajaxurl,
            dataType: "json",
            cache : false,
            data : {
                action          : "get_post_type_items",
                nonce           : nonce_relations_ajax,
                post_type       : relation_data.dstId,
                relation_data   : relation_data.srcId,
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

                jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody').empty().append(html_to_append);


                var status_text = "";

                switch (data.status.result_type) {
                    case "available"    : status_text = "Available " + relation_data.dstLabel; break;
                    case "latest"       : status_text = "Latest " + relation_data.dstLabel + " (" + data.status.returned_results + " of " + data.status.available_results + ")"; break;
                    case "search"       : status_text = "Search results " + data.status.returned_results + " of " + data.status.available_results; break;

                }

                jQuery(relation_data.metaboxSelector + '.relation_src_list > thead > tr > th').text(status_text);
            }
        });
    }
}

function show_connected_items_box (relation_data) {
    show_box(relation_data, 'relation_connected_box');

    var data = {
        action         : "get_connected_items",
        rel_id         : relation_data.relIdClean
    };

    if (relation_data.relDir == "from_to")  data.to_id      = relation_data.postId;
    else                                    data.from_id    = relation_data.postId;

    jQuery(relation_data.metaboxSelectorConnectedItems).empty().append(
        "<div class='relations_info'><img src='" + admin_url_wpspin_light + "'> loading connected "+relation_data.dstLabel+"<br></div>"
    );

    jQuery.ajax({
        url      : ajaxurl,
        dataType : "json",
        data     : data,
        cache    : false,
        success  : function (ret) {
            var html_to_append = "";

            for (var i = ret.results.length - 1; i >= 0; i--) {
                var result = ret.results[i];

                if (result.item_metadata.post_title == "") result.item_metadata.post_title  = "<i>untitled</i>";

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

                        if ( val != undefined && (val != "" && val != "0" && val != "false" && val != 0 && val != false) ) {
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

            if (ret.results.length == 0) {
                html_to_append = "<div class='relations_info'>no "+relation_data.dstLabel+" connected yet<br>click on \"add connection\" to get started</div>";

                update_tab_label(relation_data, relation_data.label + " (0)");
            } else {
                update_tab_label(relation_data, relation_data.label + " ("+ret.results.length+")");
            }

            jQuery(relation_data.metaboxSelectorConnectedItems).empty().append(html_to_append);
        }
    });
}
function show_connected_item_edit_box (relation_data) {
    var selected_item           = jQuery(relation_data.metaboxSelector + '.relation_connected_list tr.selected');
    var selected_item_data      = selected_item.data();
    var selected_item_label     = selected_item.text();
    var object_id               = (selected_item_data.data.post_from_id != relation_data.postId ? selected_item_data.data.post_from_id : selected_item_data.data.post_to_id);

    var relationEditBox         = htmlspecialchars_decode(relation_data.relationEditBox);
    var itemEditBox             = htmlspecialchars_decode(relation_data.itemUpdateEditBox);

    var item_metabox            = jQuery(relation_data.metaboxSelector + ".relation_edit_connected_box").clone(true);

    jQuery(relation_data.metaboxSelector + ".relation_connected_box th .relation_buttons_box").hide();
    item_metabox.appendTo(relation_data.metaboxSelector + '.relation_connected_list tr.selected > td:first-child');
    item_metabox.find(".relation_buttons_box").appendTo(relation_data.metaboxSelector + ".relation_connected_box th");
    item_metabox.show();

    var info_text               = "<div class='padding_box'>"
        + ( itemEditBox != "" ? "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>Item Metadata</label>" + itemEditBox + "</div>" : "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>No Editable Item Metadata</label></div>")
        + ( relationEditBox != "" ? "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>Relation Metadata</label>" + relationEditBox + "</div>" : "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>No Editable Relation Metadata</label></div>")
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
            "object_id" : object_id,
            "item_metabox" : item_metabox
        },
        success: function (data) {
            var item_metadata = data.results;

            item_metabox.find('.relation_edit_connected_metadata_box').empty().append(info_text);

            jQuery("<table class='relation_metadata'>"
            + "<tr><td class='table_label'>relation id</td><td>" + selected_item_data.data.id + "</td></tr>"
            + "<tr><td class='table_label'>object id</td><td>" + object_id + " <a class='relation_edit_link' target='_blank' href='" + admin_url_post_php + "?post=" + object_id + "&action=edit'>edit "+relation_data.dstSingularLabel+"</a></td></tr>"
            + "</table>").prependTo(relation_data.metaboxSelector + '.relation_connected_list tr.selected > td:first-child');

            item_metabox.find(".relation_edit_connected_metadata_box .wpc_input_text").each(check_text_input_value);
            item_metabox.find(".relation_edit_connected_metadata_box .wpc_input:first").focus();

            set_metabox_data(relation_data, item_metadata, selected_item_data.data.relation_metadata);

            move_wpc_labels_to_rows();

            try {
                on_relation_metabox_ready();
            } catch (err) {

            }

            relation_data_edit_connected = relation_data;

        }
    });
}
function set_metabox_data (relation_data, item_metadata, relation_metadata) {
    for (var metadata_key in relation_metadata){
        var input_id    = '#wpc_'+relation_data.relIdClean+'_field_'+metadata_key;
        var input_val   = relation_metadata[metadata_key];
        var input       = jQuery(input_id);

        if ( !input.is(':checkbox'))    input.val(input_val);
        else                            input.attr('checked', (input_val == true || input_val == 1 || input_val == "1" || input_val == "true" || input_val == 1) );
    }

    for (var metadata_key in item_metadata){
        var input_id    = relation_data.relDir == "from_to" ? '#wpc_'+relation_data.dstId+'_field_'+metadata_key : '#wpc_'+relation_data.srcId+'_field_'+metadata_key;
        var input_val   = item_metadata[metadata_key];
        var input       = jQuery(input_id);

        if ( !input.is(':checkbox'))    input.val(input_val);
        else                            input.attr('checked', (input_val == true || input_val == 1 || input_val == "1" || input_val == "true" || input_val == 1) );
    }

}

function remove_state () {
    jQuery('table > tbody > tr > td > .relation_metadata').remove();
    jQuery('table > tbody > tr > td > .relation_edit_connected_box').remove();
    jQuery('table > tbody > tr.selected').removeClass('selected');

    jQuery('.relation_connect_new_metadata_box').empty();

    jQuery(".relation_connected_box th .relation_edit_connected_buttons_box").remove();
    jQuery(".relation_connected_box th .relation_buttons_box").show();

    jQuery(".relation_add_search_box th .relation_connect_existing_search_buttons_box").show()
    jQuery(".relation_add_search_box th .relation_connect_existing_buttons_box").hide()

    jQuery('.relation_src_list > tbody > tr.selected').removeClass('selected');

    jQuery('.relation_src_list > tbody > tr > td > .relation_metadata').remove();
    jQuery('.relation_src_list > tbody > tr > td > .relation_connect_existing_box').remove();
}
function set_selection_status (item, relation_data) {
    remove_state();

    jQuery(item).closest("tr").addClass("selected");
}

function handle_event(event_id, event, relation_data) {
    last_relation_data = relation_data;

    event.stopPropagation();
    event.preventDefault();

    switch (event_id) {
        case "a.relation_connect_existing_search" :
            show_box(relation_data, "relation_add_search_box");

            jQuery(relation_data.metaboxSelector + '.relation_src_search').val('');

            update_search_results(relation_data);
            break;

        case "a.relation_connect_existing_search_cancel" :
            remove_state();
            show_connected_items_box(relation_data);
            break;

        case "a.relation_connect_existing_cancel" :
            jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr.selected').removeClass('selected');

            jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr > td > .relation_metadata').remove();
            jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr > td > .relation_connect_existing_box').remove();

            jQuery(relation_data.metaboxSelector + '.relation_src_search').val('');

            show_box(relation_data, "relation_add_search_box");

            update_search_results(relation_data);
            jQuery(relation_data.metaboxSelector + ".relation_add_search_box th .relation_connect_existing_search_buttons_box").show()
            jQuery(relation_data.metaboxSelector + ".relation_add_search_box th .relation_connect_existing_buttons_box").hide()
            break;

        case "a.relation_source_item" :
            jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr.selected').removeClass('selected');

            jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr > td > .relation_metadata').remove();
            jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr > td > .relation_connect_existing_box').remove();

            jQuery(event.target).closest("tr").addClass("selected");

            var selected_item           = jQuery(relation_data.metaboxSelector + '.relation_src_list tbody tr.selected');
            var selected_item_data      = selected_item.data();
            var selected_item_name      = selected_item.text();
            var object_id               = selected_item_data.postId;

            var relationEditBox         = htmlspecialchars_decode(relation_data.relationEditBox);
            var itemEditBox             = htmlspecialchars_decode(relation_data.itemUpdateEditBox);

            var item_metabox            = jQuery(relation_data.metaboxSelector + ".relation_connect_existing_box").clone(true);

            jQuery(relation_data.metaboxSelector + ".relation_add_search_box th .relation_buttons_box").hide()
            item_metabox.appendTo(relation_data.metaboxSelector + '.relation_src_list tr.selected > td:first-child');
            item_metabox.find(".relation_buttons_box").appendTo(relation_data.metaboxSelector + ".relation_add_search_box th");
            item_metabox.show();

            var info_text               = "<div class='padding_box'>"
            + ( itemEditBox != "" ? "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>Item Metadata</label>" + itemEditBox + "</div>" : "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>No Editable Item Metadata</label></div>")
            + ( relationEditBox != "" ? "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>Relation Metadata</label>" + relationEditBox + "</div>" : "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>No Editable Relation Metadata</label></div>")
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
                    "object_id" : object_id,
                    "item_metabox" : item_metabox
                },
                success: function (data) {
                    var item_metadata = data.results;

                    item_metabox.find('.relation_connect_existing_metadata_box').empty().append(info_text);

                    jQuery("<table class='relation_metadata'>"
                    + "<tr><td class='table_label'>object id</td><td>" + object_id + " <a class='relation_edit_link' target='_blank' href='" + admin_url_post_php + "?post=" + object_id + "&action=edit'>edit "+relation_data.dstSingularLabel+"</a></td></tr>"
                    + "</table>").prependTo(relation_data.metaboxSelector + '.relation_src_list tr.selected > td:first-child');

                    item_metabox.find(".relation_connect_existing_metadata_box .wpc_input_text").each(check_text_input_value);
                    item_metabox.find(".relation_connect_existing_metadata_box .wpc_input:first").focus();

                    set_metabox_data(relation_data, item_metadata, {});

                    move_wpc_labels_to_rows();

                    try {
                        on_relation_metabox_ready();
                    } catch (err) {

                    }

                    relation_data_connect_existing = relation_data;
                }
            });
            break;
        case "a.relation_connect_existing_add" :
            var selected_item               = jQuery(relation_data.metaboxSelector + '.relation_src_list > tbody > tr.selected');
            var selected_item_data          = selected_item.data();
            var selected_item_label         = selected_item.text();

            var relation_metadata_fields    = jQuery(relation_data.metaboxSelector + '.relation_connect_existing_metadata_box .relation_metadata_edit_box .wpc_input');
            var item_metadata_fields        = jQuery(relation_data.metaboxSelector + '.relation_connect_existing_metadata_box .relation_item_metadata_edit_box .wpc_input');

            var data = {
                    action              : "add_relation",
                    nonce               : nonce_relations_ajax,
                    rel_id              : relation_data.relIdClean,
                    from_id             : relation_data.relDir == "to_from" ? relation_data.postId : selected_item.data('post-id'),
                    to_id               : relation_data.relDir == "to_from" ? selected_item.data('post-id') : relation_data.postId,
                    item_id             : relation_data.relDir == "to_from" ? selected_item.data('post-id') : selected_item.data('post-id'),
                    item_type           : relation_data.relDir == "to_from" ? selected_item.data('post-type') : selected_item.data('post-type'),
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
                    "relation_data"         : relation_data,
                    "data"                  : data
                },
                success: function (data) {
                    remove_state();
                    show_connected_items_box(relation_data);
                }
            });
            break;


        case "a.relation_connected_item" :
            set_selection_status(event.target, relation_data);

            show_connected_item_edit_box(relation_data);
            break;

        case "a.relation_edit_connected_cancel" :
            remove_state();
            break;

        case "a.relation_edit_connected_update" :
            var selected_item       = jQuery(relation_data.metaboxSelector + '.relation_connected_list > tbody > tr.selected');
            var selected_item_data  = selected_item.data();
            var selected_item_label = selected_item.text();

            var object_id           = (selected_item_data.data.post_from_id != relation_data.postId ? selected_item_data.data.post_from_id : selected_item_data.data.post_to_id);
            var object_type         = (selected_item_data.data.post_from_id != relation_data.postId ? relation_data.dstId : relation_data.srcId);

            var relation_metadata_fields = jQuery(relation_data.metaboxSelector + '.relation_edit_connected_metadata_box .relation_metadata_edit_box .wpc_input');
            var item_metadata_fields     = jQuery(relation_data.metaboxSelector + '.relation_edit_connected_metadata_box .relation_item_metadata_edit_box .wpc_input');

            var data = {
                    action              : "update_relation",
                    nonce               : nonce_relations_ajax,
                    rel_id              : relation_data.relIdClean,
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
                    "selected_item_data"    : selected_item_data,
                    "data"                  : data
                },
                success: function (data) {
                    remove_state();
                    show_connected_items_box(relation_data);
                }
            });

            break;


        case "a.relation_edit_connected_delete" :
            var selected_item       = jQuery(relation_data.metaboxSelector + '.relation_connected_list > tbody > tr.selected');
            var selected_item_data  = jQuery(relation_data.metaboxSelector + '.relation_connected_list > tbody > tr.selected').data();

            var data = {
                    action : "delete_relation",
                    nonce  : nonce_relations_ajax,
                    rel_id : relation_data.relIdClean,
                    id     : selected_item_data.data.id
                };

            jQuery.ajax({
                url: ajaxurl,
                dataType: "json",
                data : data,
                cache : false,
                context : {
                    "selected_item" : selected_item,
                    "selected_item_data" : selected_item_data,
                    "data" : data,
                    "relation_data" : relation_data
                },
                success: function (data) {
                    remove_state();

                    show_connected_items_box(relation_data);
                }
            });
            break;

        case "a.relation_connect_new_add" :
            var relation_metadata_fields = jQuery(relation_data.metaboxSelector + '.relation_connect_new_metadata_box .relation_metadata_edit_box .wpc_input');
            var item_metadata_fields     = jQuery(relation_data.metaboxSelector + '.relation_connect_new_metadata_box .relation_item_metadata_edit_box .wpc_input');

            var data = {
                    action              : "add_relation",
                    nonce               : nonce_relations_ajax,
                    rel_id              : relation_data.relIdClean,
                    relation_metadata   : {},
                    item_metadata       : {}
                };

            if (relation_data.relDir == "from_to")  data.to_id      = relation_data.postId;
            else                                    data.from_id    = relation_data.postId;

            data.relation_metadata  = getInputData(relation_data, relation_metadata_fields);
            data.item_metadata      = getInputData(relation_data, item_metadata_fields);

            jQuery.ajax({
                url: ajaxurl,
                dataType: "json",
                data : data,
                cache : false,
                context : {
                    "relation_data"         : relation_data,
                    "data"                  : data
                },
                success: function (data) {
                    remove_state();

                    show_connected_items_box(relation_data);
                }
            });
            break;
        case "a.relation_connect_new_input" :
            var relationEditBox         = htmlspecialchars_decode(relation_data.relationEditBox);
            var itemEditBox             = htmlspecialchars_decode(relation_data.itemNewEditBox);

            var info_text               =
              "<h3>Add new " + relation_data.dstSingularLabel + "</h3>" + "<div class='padding_box'>"
            + ( itemEditBox != ""   ? "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>Item Metadata</label>" + itemEditBox + "</div>" : "<div class='relation_item_metadata_edit_box'><label class='relation_edit_label'>No Editable Item Metadata</label></div>")
            + ( relationEditBox != ""       ? "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>Relation Metadata</label>" + relationEditBox + "</div>" : "<div class='relation_metadata_edit_box'><label class='relation_edit_label'>No Editable Relation Metadata</label></div>")
            + "</div>";

            jQuery(relation_data.metaboxSelector + '.relation_connect_new_metadata_box').empty().append(info_text);

            jQuery(relation_data.metaboxSelector + '.relation_connect_new_metadata_box').each(check_text_input_value);
            jQuery(relation_data.metaboxSelector + '.relation_connect_new_metadata_box input').first().focus();

            show_box(relation_data, "relation_connect_new_box");

            move_wpc_labels_to_rows();

            try {
                on_relation_metabox_ready();
            } catch (err) {

            }
            break;
        case "a.relation_connect_new_cancel" :
            show_box(relation_data, "relation_connected_box");
            break;
    }

    return false;
}

function init_relation (relation_data) {
    relation_data.metaboxSelector                   = ".relation_edit_box." + relation_data.relId + " ";
    relation_data.metaboxSelectorConnectedItems     = relation_data.metaboxSelector + ".relation_connected_list tbody";

    relation_data.dstLabel                          = relation_data.relDir != "from_to" ? relation_data.toLabel             : relation_data.fromLabel;
    relation_data.srcLabel                          = relation_data.relDir != "from_to" ? relation_data.fromLabel           : relation_data.toLabel;
    relation_data.dstSingularLabel                  = relation_data.relDir != "from_to" ? relation_data.toSingularLabel     : relation_data.fromSingularLabel;
    relation_data.srcSingularLabel                  = relation_data.relDir != "from_to" ? relation_data.fromSingularLabel   : relation_data.toSingularLabel;
    relation_data.dstId                             = relation_data.relDir != "from_to" ? relation_data.toId                : relation_data.fromId;
    relation_data.srcId                             = relation_data.relDir != "from_to" ? relation_data.fromId              : relation_data.toId;

    var relation_metabox        = jQuery(relation_data.metaboxSelector);

    show_connected_items_box(relation_data);

    relation_metabox.delegate("a.relation_connected_item",                      "click", function(event) { return handle_event("a.relation_connected_item",                     event, relation_data); } );
    relation_metabox.delegate("a.relation_edit_connected_cancel",               "click", function(event) { return handle_event("a.relation_edit_connected_cancel",              event, relation_data); } );
    relation_metabox.delegate("a.relation_edit_connected_delete",               "click", function(event) { return handle_event("a.relation_edit_connected_delete",              event, relation_data); } );
    relation_metabox.delegate("a.relation_edit_connected_update",               "click", function(event) { return handle_event("a.relation_edit_connected_update",              event, relation_data); } );

    relation_metabox.delegate("a.relation_connect_new_cancel",                  "click", function(event) { return handle_event("a.relation_connect_new_cancel",                 event, relation_data); } );
    relation_metabox.delegate("a.relation_connect_new_input",                   "click", function(event) { return handle_event("a.relation_connect_new_input",                  event, relation_data); } );
    relation_metabox.delegate("a.relation_connect_new_add",                     "click", function(event) { return handle_event("a.relation_connect_new_add",                    event, relation_data); } );

    relation_metabox.delegate("a.relation_connect_existing_search",             "click", function(event) { return handle_event("a.relation_connect_existing_search",            event, relation_data); } );
    relation_metabox.delegate("a.relation_connect_existing_search_cancel",      "click", function(event) { return handle_event("a.relation_connect_existing_search_cancel",     event, relation_data); } );
    relation_metabox.delegate("a.relation_source_item",                         "click", function(event) { return handle_event("a.relation_source_item",                        event, relation_data); } );
    relation_metabox.delegate("a.relation_connect_existing_cancel",             "click", function(event) { return handle_event("a.relation_connect_existing_cancel",            event, relation_data); } );
    relation_metabox.delegate("a.relation_connect_existing_add",                "click", function(event) { return handle_event("a.relation_connect_existing_add",               event, relation_data); } );

    relation_metabox.delegate('.relation_src_search',               'keyup', function(event) {
        update_search_results(relation_data);
    });
}

/*
 *  global delegate to close opened rel-edit screens when clicking outside
 */

var last_relation_data  = null;
var last_filter_value   = "";

jQuery(document).mouseup(function (e) {
    var src          = jQuery(e.srcElement);
    var is_outside   = jQuery(src).closest("tr.selected, .relation_buttons_box, .relation_connect_new_metadata_box, .relation_src_list").length == 0;

    if (is_outside && last_relation_data != null) {
        remove_state();
        show_box(last_relation_data, 'relation_connected_box');

        last_relation_data = null
    };

});

