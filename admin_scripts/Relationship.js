function goto_box (relation_data, to_box_id, from_box_id, direction) {
    var base_id = ".relation_edit_box." + relation_data.relId + " ";


    switch (from_box_id) {
        case "relation_add_search_box":
            if (to_box_id != "relation_connect_existing_box") {
                jQuery(base_id + '.relation_src_search').val('');
                jQuery(base_id + '.relation_src_list').empty();
            }
            break;
        case "relation_connect_existing_box" :
            jQuery(base_id + '.relation_connect_existing_metadata_box').empty();
            break;
        case "relation_edit_connected_box" :
            jQuery(base_id + '.relation_edit_connected_metadata_box').empty();
            break;
        case "relation_connect_new_box" :
            jQuery(base_id + '.new_item_title').val('');
            jQuery(base_id + '.relation_connect_new_metadata_box').empty();
            break;
    }


    jQuery(base_id + ' > div:visible').hide();
    jQuery(base_id + '.'+to_box_id).show();


    switch (to_box_id) {
        case "relation_connected_box" :
            set_connected_items(relation_data);
            break;
        case "relation_connect_existing_box" :
            var selected_item           = jQuery(base_id + '.relation_src_list li.selected');
            var selected_item_data      = jQuery(selected_item).data();
            var object_id               = selected_item_data.postId;
            var selected_item_name      = jQuery(base_id + '.relation_src_list li.selected a').text();

            var info_text               =
            "<b>" + selected_item_name + "</b> <a class='relation_edit_link' target='_blank' href='" + admin_url_post_php + "?post=" + object_id + "&action=edit'>edit "+relation_data.dstSingularLabel+"</a><br/>"
            + "<table>"
            + "<tr><td class='table_label'>object id</td><td>" + object_id + "</td></tr>"
            + "</table>" + htmlspecialchars_decode(relation_data.editBox);

            jQuery(base_id + '.relation_connect_existing_metadata_box').empty().append(info_text);

            if (relation_data.editBox == "") {
                jQuery(base_id + '.relation_connect_existing_add').focus();
            } else {
                jQuery(base_id + ".relation_connect_existing_metadata_box .wpc_input_text").each(check_text_input_value);
                jQuery(base_id + ".relation_connect_existing_metadata_box .wpc_input:first").focus();
            }
            break;
        case "relation_edit_connected_box" :
            var selected_item           = jQuery(base_id + '.relation_conected_list li.selected');
            var selected_item_data      = jQuery(selected_item).data();
            var object_id               = (selected_item_data.data.post_from_id != relation_data.postId ? selected_item_data.data.post_from_id : selected_item_data.data.post_to_id);

            var info_text               =
            "<b>" + selected_item_data.data.post_title + "</b> <a class='relation_edit_link' target='_blank' href='" + admin_url_post_php + "?post=" + object_id + "&action=edit'>edit "+relation_data.dstSingularLabel+"</a><br/>"
            + "<table>"
            + "<tr><td class='table_label'>relation id</td><td>" + selected_item_data.data.id + "</td></tr>"
            + "<tr><td class='table_label'>object id</td><td>" + object_id + "</td></tr>"
            + "</table>" + htmlspecialchars_decode(relation_data.editBox);

            jQuery(base_id + '.relation_edit_connected_metadata_box').empty().append(info_text);

            if (relation_data.editBox == "") {
                jQuery(base_id + '.relation_edit_connected_update').focus();
            } else {
                jQuery(base_id + ".relation_edit_connected_metadata_box .wpc_input_text").each(check_text_input_value);
                jQuery(base_id + ".relation_edit_connected_metadata_box .wpc_input:first").focus();

                for (var metadata_key in selected_item_data.data.metadata){
                    jQuery('#wpc_'+relation_data.relId+'_field_'+metadata_key).val(selected_item_data.data.metadata[metadata_key]);
                }
            }
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
    goto_box(relation_data, 'relation_connected_box', 'relation_edit_connected', 'back');
}

function relation_edit_connected_update (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    var selected_item       = jQuery(base_id + '.relation_conected_list li.selected');
    var selected_item_data  = jQuery(base_id + '.relation_conected_list li.selected').data();

    var metadata_fields     = jQuery(base_id + '.relation_edit_connected_metadata_box .wpc_input');

    var data = {
            action   : "update_relation",
            nonce    : nonce_relations_ajax,
            rel_id   : relation_data.relId,
            id       : selected_item_data.data.id,
            from_id  : selected_item_data.data.post_from_id,
            to_id    : selected_item_data.data.post_to_id,
            metadata : {}
        };

    for (var i = metadata_fields.length - 1; i >= 0; i--) {
        var metadata_field = jQuery(metadata_fields[i]);

        metadata_key = metadata_field.attr('id').replace("wpc_"+relation_data.relId+"_field_", "")

        data.metadata[metadata_key] = metadata_field.val();
    };

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        success: function (data) {
            goto_box(relation_data, 'relation_connected_box', 'relation_edit_connected', 'back');
            show_status_message(relation_data, relation_data.dstLabel + " updated");
        }
    });
}

function relation_edit_connected_delete (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";
    var selected_item       = jQuery(base_id + '.relation_conected_list li.selected');
    var selected_item_data  = jQuery(base_id + '.relation_conected_list li.selected').data();

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
        success: function (data) {
            goto_box(relation_data, 'relation_connected_box', 'relation_edit_connected', 'back');
            show_status_message(relation_data, relation_data.dstLabel + " deleted");
        }
    });
}

function show_status_message(relation_data, message) {
    jQuery(".relation_edit_box." + relation_data.relId + " .status-update").text("asdf").show().delay(2000).fadeOut();
}

function relation_connect_existing_add (relation_data) {
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    var selected_item       = jQuery(base_id + '.relation_src_list li.selected');

    var metadata_fields     = jQuery(base_id + '.relation_connect_existing_box .wpc_input');

    var data = {
            action       : "add_relation",
            nonce        : nonce_relations_ajax,
            rel_id       : relation_data.relId,
            from_id      : relation_data.relDir == "to_from" ? relation_data.postId : selected_item.data('post-id'),
            to_id        : relation_data.relDir == "to_from" ? selected_item.data('post-id') : relation_data.postId,

            metadata     : {}
        };

    for (var i = metadata_fields.length - 1; i >= 0; i--) {
        var metadata_field = jQuery(metadata_fields[i]);

        metadata_key = metadata_field.attr('id').replace("wpc_"+relation_data.relId+"_field_", "")

        data.metadata[metadata_key] = metadata_field.val();
    };

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        success: function (data) {
            goto_box(relation_data, 'relation_add_search_box', 'relation_connect_existing_box', 'back');
            show_status_message(relation_data, relation_data.dstLabel + " added");
        }
    });
}

function relation_connect_existing_cancel (relation_data) {
    goto_box(relation_data, 'relation_add_search_box', 'relation_connect_existing_box', 'back');
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
    jQuery(base_id + '.relation_conected_list').empty().append(html_to_append);

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        cache : false,
        success: function (ret) {
            var html_to_append = "";

            for (var i = ret.results.length - 1; i >= 0; i--) {
                var result = ret.results[i];

                html_to_append =
                "<li data-id='"+result.id+"' data-data='"+json_encode(result)+"'>" +
                    (relation_data.fieldToShowInList != "" && result.metadata[relation_data.fieldToShowInList] != undefined ? "<span class='connected_item_info'>"+result.metadata[relation_data.fieldToShowInList]+"</span> " : "") +
                    "<a href='#' class='relation_connected_item'>"+result.post_title+"</a> "
                + "</li>\n" + html_to_append;
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

            jQuery(base_id + '.relation_conected_list').empty().append(html_to_append);
        }
    });
}

function relation_connect_new_add (relation_data) {
    var base_id = ".relation_edit_box." + relation_data.relId + " ";

    var selected_relation   = jQuery(base_id + ".relation_selector option:selected");
    var selected_item       = jQuery(base_id + '.relation_src_list li.selected');

    var metadata_fields     = jQuery(base_id + '.relation_connect_new_box .wpc_input');

    var data = {
            action       : "add_relation",
            nonce        : nonce_relations_ajax,
            rel_id       : relation_data.relId,
            new_post_title : jQuery(base_id + '.new_item_title').val(),

            metadata     : {}
        };

    for (var i = metadata_fields.length - 1; i >= 0; i--) {
        var metadata_field = jQuery(metadata_fields[i]);

        metadata_key = metadata_field.attr('id').replace("wpc_"+relation_data.relId+"_field_", "")

        if ( metadata_key != "new_item_title") {
            data.metadata[metadata_key] = metadata_field.val();
        }
    };

    if (relation_data.relDir == "to_from") {
        data.from_id = relation_data.postId;
    } else {
        data.to_id = relation_data.postId;
    }

    jQuery.ajax({
        url: ajaxurl,
        dataType: "json",
        data : data,
        success: function (data) {
            goto_box(relation_data, 'relation_connected_box', 'relation_connect_new_box', 'back');
            show_status_message(relation_data, relation_data.dstLabel + " added");
        }
    });
}

function relation_connect_new_cancel (relation_data) {
    goto_box(relation_data, 'relation_connected_box', 'relation_connect_new_box', 'back');
}


function relation_init_metabox (relation_data) {
    goto_box(relation_data, 'relation_connected_box', '', '');
}


function relation_setup_delegates (relation_metabox_id) {
    var relation_metabox    = jQuery(relation_metabox_id);
    var relation_data       = relation_metabox.data();
    var base_id             = ".relation_edit_box." + relation_data.relId + " ";

    relation_init_metabox(relation_data);

    var last_filter_value  = "";

    jQuery(relation_metabox_id).delegate('a.relation_connected_item', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        jQuery(this).parent().addClass("selected");

        goto_box(relation_data, 'relation_edit_connected_box', 'relation_connected_box', 'forward');

        return false;
    });

    jQuery(relation_metabox_id).delegate('a.relation_connected_add', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        var html_to_append = "<div class='relations_info'>Type to search ...<br></div>";
        jQuery(base_id + '.relation_src_list').empty().append(html_to_append);

        goto_box(relation_data, 'relation_add_search_box', 'relation_connected_box', 'forward');

        jQuery(base_id + '.relation_src_search').focus();

        return false;
    });

    jQuery(relation_metabox_id).delegate('.relation_src_search', 'keydown keypress', function(event) {
        var selected = jQuery(base_id + '.relation_src_list li.selected');

        if ( event.keyCode == 13 && event.type == "keypress") {
            event.preventDefault();

            add_selected_item(relation_data, selected);
        }
        if ( event.keyCode == 40) {
            selected.removeClass('selected')
            selected.next().addClass('selected');

            if (selected.next().length == 0) {
                jQuery(base_id + '.relation_src_list li:first').addClass('selected');
            }

            event.preventDefault();
        }
        if ( event.keyCode == 27) {
            event.preventDefault();

            goto_box(relation_data, 'relation_connected_box', 'relation_add_search_box', 'back');
        }
        if ( event.keyCode == 38) {
            selected.removeClass('selected')
            selected.prev().addClass('selected');

            if (selected.prev().length == 0) {
                jQuery(base_id + '.relation_src_list li:last').addClass('selected');
            }

            event.preventDefault();
        }
        if ( event.keyCode == 23) {
            selected.removeClass('selected')
            selected.prev().addClass('selected');

            if (selected.prev().length == 0) {
                jQuery(base_id + '.relation_src_list li:last').addClass('selected');
            }

            event.preventDefault();
        }

        if (jQuery(base_id + '.relation_src_list li.selected').prev().length == 1) {
            jQuery(base_id + '.relation_src_list li.selected').prev()[0].scrollIntoView();
        } else if (jQuery(base_id + '.relation_src_list li.selected').length == 1) {
            jQuery(base_id + '.relation_src_list li.selected')[0].scrollIntoView();
        }
    });

    jQuery(relation_metabox_id).delegate('.relation_src_search', 'keyup', function(event) {
        var relation_src_search = jQuery(this);
        var filter_value        = relation_src_search.val();

        if (filter_value != last_filter_value) {
            last_filter_value = filter_value;

            if (filter_value.length >= 2) {

                var html_to_append = "<div class='relations_info'><img src='" + admin_url_wpspin_light + "'> searching for "+relation_data.dstLabel+" like \""+filter_value+"\"<br></div>";
                jQuery(base_id + '.relation_src_list').empty().append(html_to_append);

                jQuery.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    cache : false,
                    data : {
                        action      : "get_post_type_items",
                        nonce       : nonce_relations_ajax,
                        post_type   : relation_data.srcId,
                        filter      : filter_value,
                        offset      : 0,
                        limit       : 100,
                        order       : "asc",
                        order_by    : "title"
                    },
                    success: function (data) {
                        var html_to_append = "";

                        for (var i = data.results.length - 1; i >= 0; i--) {
                            var result = data.results[i];

                            html_to_append = "<li data-post-id='"+result.ID+"'><a href='#' class='relation_source_item'>"+result.post_title+"</a></li>\n" + html_to_append;
                        }

                        if (data.results.length == 0) {
                            html_to_append = "<div class='relations_info'>no "+relation_data.dstLabel+" like \""+filter_value+"\" found<br></div>";
                        };

                        jQuery(base_id + '.relation_src_list').empty().append(html_to_append);

                        jQuery(base_id + '.relation_src_list li:first').addClass('selected');
                    }
                });
            } else {
                var html_to_append = "<div class='relations_info'>Type to search ...<br></div>";
                jQuery(base_id + '.relation_src_list').empty().append(html_to_append);
            }
        }
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

        jQuery(base_id + '.relation_src_list li.selected').removeClass('selected');

        jQuery(this).parent().addClass('selected');

        add_selected_item (relation_data);

        return false;
    });

    jQuery(relation_metabox_id).delegate('a.relation_connected_add_new', 'click', function(event) {
        event.stopPropagation();
        event.preventDefault();

        goto_box(relation_data, 'relation_connect_new_box', 'relation_connected_box', 'forward');

        if (relation_data.editBox == "") {
            jQuery(base_id + '.relation_connect_new_metadata_box').empty();
        } else {
            jQuery(base_id + '.relation_connect_new_metadata_box').empty().append(htmlspecialchars_decode(relation_data.editBox)).show();

            jQuery(base_id + ".relation_connect_new_metadata_box .wpc_input_text").each(check_text_input_value);
        }

        jQuery(base_id + ".new_item_title").focus();

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

        var connected = jQuery(base_id + '.relation_conected_list li');
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

    jQuery(relation_metabox_id).delegate('.relation_edit_connected_box .wpc_input', 'keydown keypress', function(event) {
        if ( event.keyCode == 13 ) {
            event.preventDefault();

            if ( event.type == "keydown" ) {
                relation_edit_connected_update ();
            }
        }
        if ( event.keyCode == 27 ) {
            event.preventDefault();

            if ( event.type == "keydown" ) {
                relation_edit_connected_cancel ();
            }
        }
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

    jQuery(relation_metabox_id).delegate('.relation_connect_existing_box .wpc_input', 'keydown keypress', function(event) {
        if ( event.keyCode == 13 ) {
            event.preventDefault();

            if ( event.type == "keydown" ) {
                relation_connect_existing_add (relation_data);
            }
        }
        if ( event.keyCode == 27 ) {
            event.preventDefault();

            if ( event.type == "keydown" ) {
                relation_connect_existing_cancel (relation_data);
            }
        }
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

    jQuery(relation_metabox_id).delegate('.relation_connect_new_box .wpc_input', 'keydown keypress', function(event) {
        if ( event.keyCode == 13 ) {
            event.preventDefault();

            if ( event.type == "keydown" ) {
                relation_connect_new_add (relation_data);
            }
        }
        if ( event.keyCode == 27 ) {
            event.preventDefault();

            if ( event.type == "keydown" ) {
                relation_connect_new_cancel (relation_data);
            }
        }
    });
}
