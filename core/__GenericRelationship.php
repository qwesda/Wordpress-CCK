<?php


global $wpc_relationships;
$wpc_relationships = array();

class __GenericRelationship {
    public $id                  = "";
    public $fields              = NULL;

    public $label               = "";

    public $post_type_from_id   = "";
    public $post_type_from      = NULL;
    public $post_type_to_id     = "";
    public $post_type_to        = NULL;

    function __construct () {
        global $wpc_relationships;
        global $wpc_content_types;

//  SET DEFAULTS
        if (empty($this->id))       $this->id       = strtolower( get_class($this) );

        if (empty($this->label))    $this->label    = $this->id;

        if ( !in_array( $this->post_type_from_id, get_post_types() ) ) {
            die ("in wpc relation \"$this->id\" is post_type_from not a valid wpc content_type\npost_type_from_id == \"$this->post_type_from_id\"");

            return ;
        } else {
            $this->post_type_from       = get_post_type_object($this->post_type_from_id);
        }

        if ( !in_array($this->post_type_to_id, get_post_types()) ) {
            die ("in wpc relation \"$this->id\" is post_type_to not a valid wpc content_type\npost_type_from_id == \"$this->post_type_to_id\"");

            return ;
        } else {
            $this->post_type_to         = get_post_type_object($this->post_type_to_id);
        }


        if (isset($wpc_ids[$this->id]) ) {
            die ("wpc relation \"$this->id\" is not unique");

            return ;
        } else {
            $wpc_relationships[$this->id] = $this;
        }

    }

    static function echo_relations_metabox ($post) {
        global $wpc_relationships;

        ?><select id="relation_selector"><?php

        foreach ($wpc_relationships as $wpc_relationship_key => $wpc_relationship) {
            if ($post->post_type == $wpc_relationship->post_type_from_id || $post->post_type == $wpc_relationship->post_type_to_id) {
                $rel_direction = $post->post_type == $wpc_relationship->post_type_from_id ? "to_from" : "from_to";

                $src_id  = $rel_direction == "to_from" ? $wpc_relationship->post_type_to_id   : $wpc_relationship->post_type_from_id;
                $dst_id  = $rel_direction == "to_from" ? $wpc_relationship->post_type_from_id : $wpc_relationship->post_type_to_id;

                $src    = $rel_direction == "from_to" ? $wpc_relationship->post_type_to     : $wpc_relationship->post_type_from;
                $dst    = $rel_direction == "from_to" ? $wpc_relationship->post_type_from   : $wpc_relationship->post_type_to;

                ?>
                <option value = "<?php echo $wpc_relationship->id ?>"
                        class = "<?php echo $wpc_relationship->id ?>"

                 data-rel-dir = "<?php echo $rel_direction ?>"

                  data-rel-id = "<?php echo $wpc_relationship->id ?>"
                  data-src-id = "<?php echo $src_id ?>"
                  data-dst-id = "<?php echo $dst_id ?>"

               data-src-label = "<?php echo $src->label ?>"
               data-dst-label = "<?php echo $dst->label ?>"

      data-src-singular-label = "<?php echo $src->singular_label ?>"
      data-dst-singular-label = "<?php echo $dst->singular_label ?>"
                data-edit-box = "<?php echo $wpc_relationship->echo_item_metabox_str() ?>"
                 data-post-id = "<?php echo $post->ID ?>"
                ><?php echo $wpc_relationship->label ?></option><?php
            }
        }

        ?><select>
        <div id="relation_edit_boxes">
            <script type="text/javascript" charset="utf-8">
                function goto_box (to_box_id, from_box_id, direction) {
                    var selected_relation       = jQuery("#relation_selector option:selected");
                    var selected_relation_data  = selected_relation.data()
                    
                    switch (from_box_id) {
                        case "relation_add_search_box":
                            if (to_box_id != "relation_connect_existing_box") {
                                jQuery('#relation_src_search').val('');
                                jQuery('#relation_src_list').empty();
                            }
                            break;
                        case "relation_connect_existing_box" :
                            jQuery('#relation_connect_existing_metadata_box').empty();
                            break;
                        case "relation_edit_connected_box" :
                            jQuery('#relation_edit_connected_metadata_box').empty();
                            break;
                        case "relation_connect_new_box" :
                            jQuery('#new_item_title').val('');
                            jQuery('#relation_connect_new_metadata_box').empty();
                            break;
                    }
                    
                    jQuery('#relation_edit_boxes > div > div.relation_nav_bar').empty();
                    
    
                    if (direction == "back") {
                        jQuery('#relation_edit_boxes > div:visible').hide();
                        jQuery('#'+to_box_id).show();
                    } else if (direction == "forward") {
                        jQuery('#relation_edit_boxes > div:visible').hide();
                        jQuery('#'+to_box_id).show();
                    } else {
                        jQuery('#relation_edit_boxes > div:visible').hide();
                        jQuery('#'+to_box_id).show();
                    }


                    switch (to_box_id) {
                        case "relation_connected_box" :
                            set_connected_items();
                            
                            jQuery('#'+to_box_id + ' > div.relation_nav_bar').append('<div>Connected '+selected_relation.data('dst-label')+'</div>');
                            break;
                        case "relation_add_search_box":
                            jQuery('#relation_src_search').focus();
                            
							jQuery('#relation_connected_add_new').text('connect new ' + selected_relation.data('dst-singular-label'));
							
                            jQuery('#'+to_box_id + ' > div.relation_nav_bar').append('<div>Connected</div><div>Search for '+selected_relation.data('dst-label')+'</div>');
                            break;
                        case "relation_connect_existing_box" :
                            var selected_item           = jQuery('#relation_src_list li.selected a');
														
							if (selected_relation_data.editBox == "") { 
								jQuery('#relation_connect_existing_metadata_box').empty().hide();
								
								jQuery('#relation_connect_existing_add').focus();
							} else {
								jQuery('#relation_connect_existing_metadata_box').empty().append(htmlspecialchars_decode(selected_relation_data.editBox)).show();
								
		                       	jQuery("#relation_connect_existing_metadata_box .wpc_input_text").each(check_text_input_value);
		                        jQuery("#relation_connect_existing_metadata_box .wpc_input:first").focus();
							}
                        
                            
                            jQuery('#'+to_box_id + ' > div.relation_nav_bar').append('<div>Connected</div><div>Search</div><div>Add '+selected_item.text()+'</div>');
                            break;
                        case "relation_edit_connected_box" :
                            var selected_item           = jQuery('#relation_conected_list li.selected a');
                            var selected_item_data      = jQuery('#relation_conected_list li.selected').data();
     
							if (selected_relation_data.editBox == "") { 
								jQuery('#relation_edit_connected_metadata_box').empty().hide();
								
								jQuery('#relation_edit_connected_update').focus();
							} else {
								jQuery('#relation_edit_connected_metadata_box').empty().append(htmlspecialchars_decode(selected_relation_data.editBox)).show();
								
                            	jQuery("#relation_edit_connected_metadata_box .wpc_input_text").each(check_text_input_value);
                            	jQuery("#relation_edit_connected_metadata_box .wpc_input:first").focus();

	                            for (var metadata_key in selected_item_data.data.metadata){
	                                jQuery('#wpc_'+selected_relation_data.relId+'_'+metadata_key).val(selected_item_data.data.metadata[metadata_key]);
	                            }
							}
                            
                            jQuery('#'+to_box_id + ' > div.relation_nav_bar').append('<div>Connected</div><div>Edit '+selected_item.text()+'</div>');
                            break;
                        case "relation_connect_new_box" :   							
							if (selected_relation_data.editBox == "") { 
								jQuery('#relation_connect_new_metadata_box').empty().hide();
							} else {
								jQuery('#relation_connect_new_metadata_box').empty().append(htmlspecialchars_decode(selected_relation_data.editBox)).show();
								
	                            jQuery("#relation_connect_new_metadata_box .wpc_input_text").each(check_text_input_value);
							}	
	                           
							jQuery("#new_item_title").focus();
							
                            jQuery('#new_item_title').text('title for new ' + selected_relation.data('dst-singular-label'));
							
                            
                            jQuery('#'+to_box_id + ' > div.relation_nav_bar').append('<div>Connected</div><div>Search</div><div>Add new '+selected_relation.data('dst-singular-label')+'</div>');
                            break;
                    }

                    
                }
            </script>
            
            <div id="relation_connected_box">
                <div class="relation_nav_bar"></div><div class="clear"></div>
                
                <ul id="relation_conected_list">

                </ul>
    
                <div id="relation_buttons_box" class="relation_buttons_box">
                    <a id='relation_connected_add' class="button" href='#'>add connection</a>
                </div>
                
                <script type="text/javascript" charset="utf-8">
                    jQuery('body').delegate('a.relation_connected_item', 'click', function(event) {
                        event.preventDefault();
                        
                        jQuery(this).parent().addClass("selected");

                        goto_box('relation_edit_connected_box', 'relation_connected_box', 'forward');
                    });
                    jQuery('body').delegate('a#relation_connected_add', 'click', function(event) {
                        event.preventDefault();
                        
                        goto_box('relation_add_search_box', 'relation_connected_box', 'forward');
                    });
                </script>
            </div>

            <div id="relation_add_search_box" class="hidden">
                <div class="relation_nav_bar"></div><div class="clear"></div>
                
                <div>
                    <input type="text" class="wpc_input_text" id="relation_src_search" >
                    <label class="wpc_hint" for="relation_src_search" id="wpc_input_text_hint">search for existing item</label> or
                    <a id='relation_connected_add_new' class="button" href='#'>connect new item</a>
                </div>

                <ul id="relation_src_list">

                </ul>
    
                <div id="relation_add_buttons_box" class="relation_buttons_box">
                    <a id='relation_add_search_cancel' class="button" href='#'>back</a>
                </div>
                
                <script type="text/javascript" charset="utf-8">
                    var last_filter_value  = "";
        
                    jQuery('body').delegate('#relation_src_search', 'keydown keypress', function(event) {
                        var selected = jQuery('#relation_src_list li.selected');

                        if ( event.keyCode == 13 && event.type == "keypress") {
                            event.preventDefault();
                            
                            add_selected_item(selected);
                        }
                        if ( event.keyCode == 40) {
                            selected.removeClass('selected')
                            selected.next().addClass('selected');

                            if (selected.next().length == 0) {
                                jQuery('#relation_src_list li:first').addClass('selected');
                            }

                            event.preventDefault();
                        }
                        if ( event.keyCode == 27) {
                            event.preventDefault();
        
                            goto_box('relation_connected_box', 'relation_add_search_box', 'back');
                        }
                        if ( event.keyCode == 38) {
                            selected.removeClass('selected')
                            selected.prev().addClass('selected');

                            if (selected.prev().length == 0) {
                                jQuery('#relation_src_list li:last').addClass('selected');
                            }

                            event.preventDefault();
                        }
                        if ( event.keyCode == 23) {
                            selected.removeClass('selected')
                            selected.prev().addClass('selected');

                            if (selected.prev().length == 0) {
                                jQuery('#relation_src_list li:last').addClass('selected');
                            }

                            event.preventDefault();
                        }

                        if (jQuery('#relation_src_list li.selected').prev().length == 1) {
                            jQuery('#relation_src_list li.selected').prev()[0].scrollIntoView();
                        } else if (jQuery('#relation_src_list li.selected').length == 1) {
                            jQuery('#relation_src_list li.selected')[0].scrollIntoView();
                        }
                    });
                    jQuery('body').delegate('#relation_src_search', 'keyup', function(event) {
                        var selected_relation   = jQuery("#relation_selector option:selected");
                        var relation_data       = selected_relation.data()

                        var relation_src_search = jQuery(this);
                        var filter_value        = relation_src_search.val();

                        if (filter_value != last_filter_value) {
                            last_filter_value = filter_value;

                            if (filter_value.length >= 2) {

								var html_to_append = "<div class='relations_info'><img src='<?php echo admin_url('images/wpspin_light.gif') ?>'> searching for "+relation_data.dstLabel+" like \""+filter_value+"\"<br></div>";
								jQuery('#relation_src_list').empty().append(html_to_append);
								
                                jQuery.ajax({
                                    url: ajaxurl,
                                    dataType: "json",
                                    cache : false,
                                    data : {
                                        action      : "get_post_type_items",
                                        nonce       : "<?php echo wp_create_nonce('relations_ajax'); ?>",
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

                                        jQuery('#relation_src_list').empty().append(html_to_append);

                                        jQuery('#relation_src_list li:first').addClass('selected');
                                    }
                                });
                            } else jQuery('#relation_src_list').empty();
                        }
                    });
                    jQuery('body').delegate('a#relation_add_search_cancel', 'click', function(event) {
                        event.preventDefault();
                        
                        relation_add_search_cancel();
                    });
                    jQuery('body').delegate('a.relation_source_item', 'click', function(event) {
                        event.preventDefault();
                        jQuery('#relation_src_list li.selected').removeClass('selected');

                        jQuery(this).parent().addClass('selected');

                        add_selected_item ();
                    });
                    
                    jQuery('body').delegate('a#relation_connected_add_new', 'click', function(event) {
                        event.preventDefault();
                        
                        goto_box('relation_connect_new_box', 'relation_connected_box', 'forward');

                    });
                    function relation_add_search_cancel () {
                        goto_box('relation_connected_box', 'relation_add_search_box', 'back');
                    }
        
                    function add_selected_item () {
                        goto_box('relation_connect_existing_box', 'relation_add_search_box', 'forward');
                    }
                </script>
            </div>

            <div id="relation_edit_connected_box" class="hidden">   
                <div class="relation_nav_bar"></div><div class="clear"></div>
                        
                <div id="relation_edit_connected_metadata_box">
                
                </div>
            
                <div id="relation_edit_connected_buttons_box" class="relation_buttons_box">
                    <a id='relation_edit_connected_cancel' class="button" href='#'>cancel</a>
                    <a id='relation_edit_connected_delete' class="button" href='#'>delete</a>
                    <a id='relation_edit_connected_update' class="button-primary" href='#'>save</a>
                </div>
                
                <script type="text/javascript" charset="utf-8">
                    function relation_edit_connected_cancel () {
                        goto_box('relation_connected_box', 'relation_edit_connected', 'back');
                    }
                    function relation_edit_connected_update () {
                        var selected_relation   = jQuery("#relation_selector option:selected");
                        var relation_data       = selected_relation.data();
                        var selected_item       = jQuery('#relation_conected_list li.selected');
                        var selected_item_data  = jQuery('#relation_conected_list li.selected').data();

                        var metadata_fields     = jQuery('#relation_edit_connected_metadata_box .wpc_input');

                        var data = {
                                action       : "update_relation",
                                nonce        : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                                rel_id       : relation_data.relId,
                                relation_id  : selected_item_data.data.relation_id,
                                from_id      : selected_item_data.data.post_from_id,
                                to_id        : selected_item_data.data.post_to_id,
                                metadata     : {}
                            };

                        for (var i = metadata_fields.length - 1; i >= 0; i--) {
                            var metadata_field = jQuery(metadata_fields[i]);

                            metadata_key = metadata_field.attr('id').replace("wpc_"+relation_data.relId+"_", "")

                            data.metadata[metadata_key] = metadata_field.val();
                        };

                        jQuery.ajax({
                            url: ajaxurl,
                            dataType: "json",
                            data : data,
                            cache : false,
                            success: function (data) {          
                                goto_box('relation_connected_box', 'relation_edit_connected', 'back');
                            }
                        });
                    }
                    function relation_edit_connected_delete () {
                        var selected_relation   = jQuery("#relation_selector option:selected");
                        var relation_data       = selected_relation.data();
                        var selected_item       = jQuery('#relation_conected_list li.selected');
                        var selected_item_data  = jQuery('#relation_conected_list li.selected').data();

                        var metadata_fields     = jQuery('#relation_edit_connected_metadata_box .wpc_input');

                        var data = {
                                action       : "delete_relation",
                                nonce        : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                                rel_id       : relation_data.relId,
                                relation_id  : selected_item_data.data.relation_id
                            };

                        jQuery.ajax({
                            url: ajaxurl,
                            dataType: "json",
                            data : data,
                            cache : false,
                            success: function (data) {          
                                goto_box('relation_connected_box', 'relation_edit_connected', 'back');
                            }
                        });
                    }
                
                    jQuery('body').delegate('a#relation_edit_connected_cancel', 'click', function(event) {
                        event.preventDefault();
                        
                        relation_edit_connected_cancel();
                    });
                    jQuery('body').delegate('a#relation_edit_connected_update', 'click', function(event) {
                        event.preventDefault();

                        relation_edit_connected_update();
                    });
                    jQuery('body').delegate('a#relation_edit_connected_delete', 'click', function(event) {
                        event.preventDefault();

                        relation_edit_connected_delete();
                    });

                    jQuery('#relation_edit_connected_box').delegate('.wpc_input', 'keydown keypress', function(event) {
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
                </script>
            </div>

            <div id="relation_connect_existing_box" class="hidden"> 
                <div class="relation_nav_bar"></div><div class="clear"></div>
                        
                <div id="relation_connect_existing_metadata_box">
                
                </div>
            
                <div id="relation_connect_existing_buttons_box" class="relation_buttons_box">
                    <a id='relation_connect_existing_cancel' class="button" href='#'>cancel</a>
                    <a id='relation_connect_existing_add' class="button-primary" href='#'>add</a>
                </div>
                
                <script type="text/javascript" charset="utf-8">
                    function relation_connect_existing_add () {
                        var selected_relation   = jQuery("#relation_selector option:selected");
                        var relation_data       = selected_relation.data();
                        var selected_item       = jQuery('#relation_src_list li.selected');

                        var metadata_fields     = jQuery('#relation_connect_existing_box .wpc_input');

                        var data = {
                                action       : "add_relation",
                                nonce        : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                                rel_id       : relation_data.relId,
                                from_id      : relation_data.relDir == "to_from" ? relation_data.postId : selected_item.data('post-id'),
                                to_id        : relation_data.relDir == "to_from" ? selected_item.data('post-id') : relation_data.postId,

                                metadata     : {}
                            };

                        for (var i = metadata_fields.length - 1; i >= 0; i--) {
                            var metadata_field = jQuery(metadata_fields[i]);

                            metadata_key = metadata_field.attr('id').replace("wpc_"+relation_data.relId+"_", "")

                            data.metadata[metadata_key] = metadata_field.val();
                        };
        
                        jQuery.ajax({
                            url: ajaxurl,
                            dataType: "json",
                            data : data,
                            cache : false,
                            success: function (data) {          
                                goto_box('relation_add_search_box', 'relation_connect_existing_box', 'back');
                            }
                        });
                    }
                    function relation_connect_existing_cancel () {
                        goto_box('relation_add_search_box', 'relation_connect_existing_box', 'back');
                    }
                
                    jQuery('body').delegate('a#relation_connect_existing_cancel', 'click', function(event) {
                        event.preventDefault();
                        
                        relation_connect_existing_cancel();
                    });
        
                    jQuery('body').delegate('a#relation_connect_existing_add', 'click', function(event) {
                        event.preventDefault();

                        relation_connect_existing_add();
                    });
        
                    jQuery('#relation_connect_existing_box').delegate('.wpc_input', 'keydown keypress', function(event) {
                        if ( event.keyCode == 13 ) {
                            event.preventDefault();

                            if ( event.type == "keydown" ) {
                                relation_connect_existing_add ();
                            }
                        }
                        if ( event.keyCode == 27 ) {
                            event.preventDefault();

                            if ( event.type == "keydown" ) {
                                relation_connect_existing_cancel ();
                            }
                        }
                    });
                </script>
            </div>

            <div id="relation_connect_new_box" class="hidden"> 
                <div class="relation_nav_bar"></div><div class="clear"></div>
                
                <div>
                    <input type="text" class="wpc_input wpc_input_text" id="new_item_title" >
                    <label class="wpc_hint" for="new_item_title" id="wpc_input_text_hint">title for the new item</label>
                </div>
                        
                <div id="relation_connect_new_metadata_box">
                
                </div>
            
                <div id="relation_connect_new_buttons_box" class="relation_buttons_box">
                    <a id='relation_connect_new_cancel' class="button" href='#'>cancel</a>
                    <a id='relation_connect_new_add' class="button-primary" href='#'>add</a>
                </div>
                
                <script type="text/javascript" charset="utf-8">
                    function relation_connect_new_add () {
                        var selected_relation   = jQuery("#relation_selector option:selected");
                        var relation_data       = selected_relation.data();
                        var selected_item       = jQuery('#relation_src_list li.selected');

                        var metadata_fields     = jQuery('#relation_connect_new_box .wpc_input');

                        var data = {
                                action       : "add_relation",
                                nonce        : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                                rel_id       : relation_data.relId,
                                new_post_title : jQuery('#new_item_title').val(),

                                metadata     : {}
                            };

                        for (var i = metadata_fields.length - 1; i >= 0; i--) {
                            var metadata_field = jQuery(metadata_fields[i]);

                            metadata_key = metadata_field.attr('id').replace("wpc_"+relation_data.relId+"_", "")

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
                                goto_box('relation_connected_box', 'relation_connect_new_box', 'back');
                            }
                        });
                    }
                    function relation_connect_new_cancel () {
                        goto_box('relation_add_search_box', 'relation_connect_new_box', 'back');
                    }
                
                    jQuery('body').delegate('a#relation_connect_new_cancel', 'click', function(event) {
                        event.preventDefault();
                        
                        relation_connect_new_cancel();
                    });
                    jQuery('body').delegate('a#relation_connect_new_add', 'click', function(event) {
                        event.preventDefault();

                        relation_connect_new_add();
                    });
        
                    jQuery('#relation_connect_new_box').delegate('.wpc_input', 'keydown keypress', function(event) {
                        if ( event.keyCode == 13 ) {
                            event.preventDefault();

                            if ( event.type == "keydown" ) {
                                relation_connect_new_add ();
                            }
                        }
                        if ( event.keyCode == 27 ) {
                            event.preventDefault();

                            if ( event.type == "keydown" ) {
                                relation_connect_new_cancel ();
                            }
                        }
                    });
                </script>
            </div>
        </div>

        <script type="text/javascript">
            set_selected_relation();

            jQuery('body').delegate('#relation_selector', 'change', set_selected_relation);

            function set_selected_relation () {
                var selected_relation = jQuery("#relation_selector option:selected");
                var relation_data = selected_relation.data()

                jQuery('#wpc_input_text_hint').text('type to search for ' + relation_data.srcLabel + ' to add');
                
                
                goto_box('relation_connected_box','','');
            }
            function set_connected_items () {
                var selected_relation   = jQuery("#relation_selector option:selected");
                var relation_data       = selected_relation.data();

                var data = {
                        action         : "get_connected_items",
                        rel_id         : relation_data.relId
                    };

                if (relation_data.relDir == "from_to") {
                    data.to_id = relation_data.postId;
                } else {
                    data.from_id = relation_data.postId;
                }

				var html_to_append = "<div class='relations_info'><img src='<?php echo admin_url('images/wpspin_light.gif') ?>'> loading connected "+relation_data.dstLabel+"<br></div>";
				jQuery('#relation_conected_list').empty().append(html_to_append);

                jQuery.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    data : data,
                    cache : false,
                    success: function (ret) {
                        var html_to_append = "";

                        for (var i = ret.results.length - 1; i >= 0; i--) {
                            var result = ret.results[i];

                            html_to_append = "<li data-relation_id='"+result.relation_id+"'  data-data='"+json_encode(result)+"'><a href='#' class='relation_connected_item'>"+result.post_title+"</a> <a class='relation_edit_link' target='_blank' href='<?php echo admin_url('post.php') ?>?post="+(result.post_from_id != relation_data.postId ? result.post_from_id : result.post_to_id)+"&action=edit'>edit "+relation_data.dstSingularLabel+"</a></li>\n" + html_to_append;
                        }

						if (ret.results.length == 0) {
							html_to_append = "<div class='relations_info'>no "+relation_data.dstLabel+" connected yet<br>click on \"add connection\" to get started</div>";
						}

                        jQuery('#relation_conected_list').empty().append(html_to_append);
                    }
                });
/**/
            }
        </script>

        <?php
    }

    function echo_item_metabox () {
        return "";
    }
    function echo_item_metabox_str () {
        ob_start();

        $this->echo_item_metabox();

        $html_str = ob_get_clean();
        $html_str = str_replace("id=\"wpc", "id=\"wpc_$this->id", $html_str);
        $html_str = htmlspecialchars($html_str);

        return htmlspecialchars($html_str);
    }

    static function hookup_ajax_functions () {
        add_action('wp_ajax_get_post_type_items',               array('__GenericRelationship', 'get_post_type_items_ajax'));
        add_action('wp_ajax_add_relation',                      array('__GenericRelationship', 'add_relation_ajax'));
        add_action('wp_ajax_update_relation',                   array('__GenericRelationship', 'update_relation_ajax'));
        add_action('wp_ajax_add_relation_with_new_post',        array('__GenericRelationship', 'add_relation_ajax'));
        add_action('wp_ajax_get_connected_items',               array('__GenericRelationship', 'get_connected_items_ajax'));
        add_action('wp_ajax_delete_relation',                   array('__GenericRelationship', 'delete_relation_ajax'));
    }

    static function add_relation ($req) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        $req = (object)$_REQUEST;
        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        if ( (!isset($req->from_id) xor !isset($req->to_id)) and !empty($req->new_post_title) && !empty($wpc_relationships[$req->rel_id]) ) {
            $relation = $wpc_relationships[$req->rel_id];
            
            if ( !isset($req->to_id) ) {
                $new_post_id = wp_insert_post ( 
                    array('post_title' => $req->new_post_title, 'post_type' => $relation->post_type_to_id) 
                );
                
                $req->to_id   = $new_post_id;
            } else {
                $new_post_id = wp_insert_post (
                    array('post_title' => $req->new_post_title, 'post_type' => $relation->post_type_from_id) 
                );

                $req->from_id = $new_post_id;
            }

            _log ("add_relation_ajax: created post $new_post_id");
        }
        
        if ($req->from_id <= 0)
            $ret->errors[] = "from_id has invalid value '$req->from_id'";
        else if ($req->to_id <= 0)
            $ret->errors[] = "to_id has invalid value '$req->to_id'";
        if (empty($wpc_relationships[$req->rel_id]))
            $ret->errors[] = "rel_id has invalid value '$req->rel_id'";
        else {
            #ibotty: there is a race between these two lines. hope, this does not matter. MYISAM does not support transactions...
            #qwesda: don't understand you mean ...

            $stmt = $wpdb->query($wpdb->prepare ("INSERT INTO wp_wpc_relations (post_from_id, post_to_id, relationship_id) VALUES (%d, %d, %s)", $req->from_id, $req->to_id, $req->rel_id));
            $id = $wpdb->insert_id;
          
            if ( !empty($req->metadata) ) {
                $sql = 'INSERT INTO wp_wpc_relations_meta (relation_id, meta_key, meta_value) VALUES (%d, %s, %s);';

                foreach ($req->metadata as $key => $value) {
                    $wpdb->query($wpdb->prepare ($sql, $id, $key, $value));
                }
            }
        }

        return $ret;
    }
    static function add_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            _log ("wp_verify_nonce($req->nonce) failed.");
            _die();
        }
        
        _log($req);
        
        $ret = __GenericRelationship::add_relation($req);
        
        echo json_encode($ret);

        die();
    }

    static function update_relation ($rel) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        $req = (object)$_REQUEST;
        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        if ($rel->from_id <= 0) {
            $ret->errors[] = "from_id has invalid value '$rel->from_id'";
        } else if ($rel->to_id <= 0) {
            $ret->errors[] = "to_id has invalid value '$rel->to_id'";
        } else if (empty($wpc_relationships[$rel->rel_id])) {
            $ret->errors[] = "rel_id has invalid value '$rel->rel_id'";
        } else if (empty($rel->relation_id)) {
            $ret->errors[] = "relation_id has invalid value '$rel->relation_id'";
        } else {
            #ibotty: there is a race between these two lines. hope, this does not matter. MYISAM does not support transactions...
            #qwesda: don't understand you mean ...
            
            $stmt = $wpdb->query($wpdb->prepare ("UPDATE wp_wpc_relations SET post_from_id=%d, post_to_id=%d, relationship_id=%s WHERE relation_id=%d;", $req->from_id, $req->to_id, $req->rel_id, $req->relation_id ) );
            
            if ( !empty($req->metadata) ) {
                $sql = 'UPDATE wp_wpc_relations_meta SET meta_value=%s WHERE relation_id=%d AND meta_key=%s;';

                foreach ($req->metadata as $key => $value) {
                    $wpdb->query($wpdb->prepare ($sql, $value, $req->relation_id, $key) );
                }
            }
        }
        
        return $ret;
    }
    static function update_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            _log ("wp_verify_nonce($req->nonce) failed.");
            _die();
        }
        
        $ret = __GenericRelationship::update_relation($req);
        
        echo json_encode($ret);

        die();
    }

    static function get_connected_items ($req) {
        global $wpdb;   

        $ret = (object)array(
                     "errors" => array (),
                     "status" => array (),
                    "results" => array (),
                );
                
        if ( empty($req->rel_id) ) {
            $ret->errors[] = "rel_id was not specified";
        } else if ( !empty($req->from_id) ) {
            $id         = $req->from_id;
            $col        = 'post_from_id';
            $othercol   = 'post_to_id';
        } else if ( !empty($req->to_id) ) {
            $id         = $req->to_id;
            $col        = 'post_to_id';
            $othercol   = 'post_from_id';
        } else {
            $ret->errors[] = "neither from_id nor to_id were specified";
        }
        
        if ( !empty($id) ) {
            $sql = "SELECT wp_posts.post_title, wp_posts.ID, wp_wpc_relations.* FROM wp_wpc_relations
              JOIN wp_posts ON wp_posts.id = wp_wpc_relations.$othercol
              WHERE $col = %d AND relationship_id = %s";
            $sql_result = $wpdb->get_results($wpdb->prepare($sql, $id, $req->rel_id));

            // add metadata
            $sql = "SELECT meta_id, meta_key, meta_value FROM wp_wpc_relations_meta
              WHERE relation_id = %d";
        
            foreach ($sql_result as &$relation_row) {
                $sql_metadata_result = $wpdb->get_results($wpdb->prepare($sql, $relation_row->relation_id));
                
                $relation_row->metadata = array();
                
                foreach ($sql_metadata_result as &$metadata_row) {
                    $relation_row->metadata[ $metadata_row->meta_key ] = $metadata_row->meta_value;
                }
            
                $ret->results[] = $relation_row;
            }
        }

        return $ret;
    }
    static function get_connected_items_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = __GenericRelationship::get_connected_items($req);

        echo json_encode($ret);
        _log($ret);

        die();
    }

    static function delete_relation ($req) {
        global $wpdb;
        
        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
            );
        
        if ( empty($req->relation_id) ) {
            _log("empty");
            $ret->errors[] = "relation_id was not specified or is not is not a valid id";
        } else {
            $sql = 'DELETE FROM wp_wpc_relations_meta WHERE relation_id = %d;';
            $wpdb->query($wpdb->prepare($sql, $req->relation_id));

            // delete row itself
            $sql = 'DELETE FROM wp_wpc_relations WHERE relation_id = %d;';
            $ret->results = $wpdb->query( $wpdb->prepare($sql, $req->relation_id) );
        }
        
        return $ret;
    }
    static function delete_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            _log ("wp_verify_nonce($req->nonce) failed.");
            _die();
        }
        
        $ret = __GenericRelationship::delete_relation($req);

        echo json_encode($ret);

        die();
    }

    static function get_post_type_items ($req) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
            );

        if ( !empty( $req->post_type ) ) {
            if ( in_array($req->post_type, get_post_types () ) ) {
                $prepared_sql_limit     = "";
                $prepared_sql_order     = "";
                $prepared_sql_like      = "";

                $prepared_sql_filter    = $wpdb->prepare(
                    "  FROM $wpdb->posts \n".
                    " WHERE $wpdb->posts.post_type  = %s \n".
                    "   AND $wpdb->posts.post_status    = 'publish'".
                    "", $req->post_type
                );

                if ( !empty($req->filter) ){
                    $prepared_sql_like  = $wpdb->prepare("   AND $wpdb->posts.post_title LIKE '%%%s%%' ", $req->filter);
                }


                if ( !isset($req->limit) )
                    $req->limit = 100;

                if ( !isset($req->offset) )
                    $req->offset = 0;

                if ( absint($req->limit) > 0 ) {
                    $prepared_sql_limit  = $wpdb->prepare(" LIMIT %d OFFSET %d", absint($req->limit), absint($req->offset));
                }


                if ( !isset($req->order_by) )
                    $req->order_by = "NULL";

                if ( ( isset($req->order_by) && in_array ($req->order_by, array ("id",  "title", "NULL")) )
                  && ( empty($req->order)    || in_array ($req->order,    array ("asc", "desc")) ) ){
                    $req->order_by  = str_replace(array("id",  "title", "NULL"), array("$wpdb->posts.ID",  "$wpdb->posts.post_title", "NULL"), $req->order_by);
                    $req->order     = ( isset($req->order) && $req->order == "desc" ) ? "DESC" : "ASC";

                    $prepared_sql_order  = $wpdb->prepare("ORDER BY $req->order_by $req->order ");
                }

                $available_count    = $wpdb->get_var    ( "SELECT COUNT(*) $prepared_sql_filter $prepared_sql_like" );
                $results            = $wpdb->get_results( "SELECT $wpdb->posts.ID,  $wpdb->posts.post_title
$prepared_sql_filter
$prepared_sql_like
$prepared_sql_order
$prepared_sql_limit" );

                $ret->status['available_results']   = $available_count;
                $ret->status['returned_results']    = count($results);

                $ret->results       = $results;
            } else {
                $ret->errors[]      = "Specified post_type '$req->post_type' is invalid.\nRegistered post_types are: " . implode(", ", array_keys( $valid_posttypes ) );
            }
        } else {
            $ret->errors[] = "No post_type was specified";
        }

        return $ret;
    }
    static function get_post_type_items_ajax() {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = __GenericRelationship::get_post_type_items($req);

        echo json_encode($ret);

        die();
    }
}

?>
