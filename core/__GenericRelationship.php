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

                $src_id  = $rel_direction == "to_from" ? $wpc_relationship->post_type_to_id : $wpc_relationship->post_type_from_id;
                $dst_id  = $rel_direction == "to_from" ? $wpc_relationship->post_type_to_id : $wpc_relationship->post_type_from_id;

                $src    = $rel_direction == "from_to" ? $wpc_relationship->post_type_to     : $wpc_relationship->post_type_from;
                $dst    = $rel_direction == "from_to" ? $wpc_relationship->post_type_to     : $wpc_relationship->post_type_from;

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


        <ul id="relation_conected_list">
            <i>connected</i>
        </ul>

        <div class="clear" />
        <div>
            <input type="text" class="wpc_input_text" id="relation_src_search" >
            <label class="wpc_hint" for="relation_src_search" id="wpc_input_text_hint">type to search for items to add</label>
        </div>

        <ul id="relation_src_list">
            
        </ul>

        <div id="add_src_box" class="hidden">

        </div>

        <script type="text/javascript">
            var last_filter_value  = "";

            function set_selected_relation () {
                var selected_relation = jQuery("#relation_selector option:selected");
                var relation_data = selected_relation.data()

                jQuery('#wpc_input_text_hint').text('type to search for ' + relation_data.srcLabel + ' to add');
            }

            function add_selected_item (item_to_add) {
                var selected_relation = jQuery("#relation_selector option:selected");
                var relation_data = selected_relation.data()

                jQuery('#add_src_box').empty().append(
                    '<div id="add_src_box_header"><label for="add_src_link">add <b>' + item_to_add.text() + '</b></label></div>'
                  + htmlspecialchars_decode(relation_data.editBox)
                  + '<div id="add_src_box_buttons">'+
                        '<a id="add_src_link" class="button button_right button-primary" href="#">add</a>' +
                        '<a id="cancel_src_link" class="button button_right" href="#">cancel</a>' +
                        '<div class="clear"></div>' +
                    '</div>'
                );

                jQuery('#add_src_box').show();
                jQuery('#relation_src_list').hide();

                jQuery("#add_src_box .wpc_input_text").each(check_text_input_value);
                jQuery("#add_src_box .wpc_input:first").focus();

            }

            function add_relation () {
                jQuery('#add_src_box').hide();
                jQuery('#relation_src_list').show();

                var selected_relation   = jQuery("#relation_selector option:selected");
                var relation_data       = selected_relation.data()
                var selected_item       = jQuery('#relation_src_list li.selected');

                var metadata_fields     = jQuery('#add_src_box .wpc_input');

                var data = {
                        action       : "add_relation",
                        nonce        : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                        rel_id       : relation_data.relId,
                        src_type_id  : relation_data.srcId,
                        dst_type_id  : relation_data.dstId,
                        from_id      : relation_data.relDir == "to_from" ? relation_data.postId : selected_item.data('post-id'),
                        to_id        : relation_data.relDir == "to_from" ? selected_item.data('post-id') : relation_data.postId,

                        metadata     : {}
                    };

                for (var i = metadata_fields.length - 1; i >= 0; i--) {
                    var metadata_field = jQuery(metadata_fields[i]);

                    data.metadata[metadata_field.attr('id')] = metadata_field.val();
                };

                console.log(data);

                jQuery.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    data : data,
                    success: function (data) {
                        console.log(data);
                    }
                });

                jQuery('#add_src_box').empty();

                jQuery("#relation_src_search").focus();
            }

            set_selected_relation();
            jQuery('body').delegate('#relation_selector', 'change', set_selected_relation);

            jQuery('body').delegate('a#add_src_link', 'click', function(event) {
                add_relation ();
            });
            jQuery('body').delegate('a#cancel_src_link', 'click', function(event) {
                jQuery('#add_src_box').hide();
                jQuery('#relation_src_list').show();

                jQuery('#add_src_box').empty();
            });
            jQuery('body').delegate('a.relation_ource_item', 'click', function(event) {
                jQuery('#relation_src_list li.selected').removeClass('selected');

                jQuery(this).parent().addClass('selected');

                add_selected_item ( jQuery(this) );
            });
             
            jQuery('#add_src_box').delegate('.wpc_input', 'keydown keypress', function(event) {
                if ( event.keyCode == 13 ) {
                    event.preventDefault();

                    if ( event.type == "keydown" ) {
                        add_relation ();
                    }
                }
            });

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

                        jQuery.ajax({
                            url: ajaxurl,
                            dataType: "json",
                            data : {
                                action      : "get_post_type_items_for_relation",
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

                                    html_to_append = "<li data-post-id='"+result.ID+"'><a href='#' class='relation_ource_item'>"+result.post_title+"</a></li>\n" + html_to_append;
                                };

                                jQuery('#relation_src_list').empty().append(html_to_append);

                                jQuery('#relation_src_list li:first').addClass('selected');
                            }
                        });
                    } else jQuery('#relation_src_list').empty();
                }
            });


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
        add_action('wp_ajax_get_post_type_items_for_relation',  array('__GenericRelationship', 'get_post_type_items_for_relation_ajax'));
        add_action('wp_ajax_add_relation',                      array('__GenericRelationship', 'add_relation_ajax'));
    }

    static function add_relation_ajax () {
        header('Content-type: text/javascript');

        _ping();

        if( empty($_REQUEST['rel_id']) || !wp_verify_nonce($_REQUEST['nonce'], 'relations_ajax') ) {
            _log("wp_verify_nonce('".$_REQUEST['nonce']."', '".$_REQUEST['rel_id']."') failed");
            _die();
        } else {
            _log("wp_verify_nonce('".$_REQUEST['nonce']."', '".$_REQUEST['rel_id']."') succeded");
        }

        $req = (object)$_REQUEST;
        $ret = __GenericRelationship::add_relation($req);

        echo json_encode($ret);
        
        die();
    }

    static function add_relation ($req) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        $from_id = absint($req->from_id);
        $to_id   = absint($req->to_id);

        if ($req->from_id == 0) $ret->errors[] = "from_id has invalid value '$req->from_id'";
        if ($req->to_id == 0)   $ret->errors[] = "to_id has invalid value '$req->to_id'";
        

        return $ret;
    }

    static function get_post_type_items_for_relation ($req) {
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
    static function get_post_type_items_for_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = __GenericRelationship::get_post_type_items_for_relation($req);

        echo json_encode($ret);
        
        die();
    }
}

?>