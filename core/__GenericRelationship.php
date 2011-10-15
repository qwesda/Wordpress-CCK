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

                $src_id  = $rel_direction == "to_from" ? $wpc_relationship->post_type_from_id : $wpc_relationship->post_type_to_id;
                $dst_id  = $rel_direction == "to_from" ? $wpc_relationship->post_type_to_id   : $wpc_relationship->post_type_from_id;

                $src    = $rel_direction == "from_to" ? $wpc_relationship->post_type_from   : $wpc_relationship->post_type_to;
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
            <a id='relation_search_add_new' href='#'>Add as new post</a>
        </div>

        <ul id="relation_src_list">

        </ul>

        <div id="add_src_box" class="hidden">

        </div>

        <div id='relationlist'>
        </div>

        <script type="text/javascript">
            var last_filter_value  = "";

            function set_selected_relation () {
                var selected_relation = jQuery("#relation_selector option:selected");
                var relation_data = selected_relation.data()

                jQuery('#wpc_input_text_hint').text('type to search for ' + relation_data.srcLabel + ' to add');
            }

            function set_connected_items () {
                var selected_relation   = jQuery("#relation_selector option:selected");
                var relation_data       = selected_relation.data();

                var data = {
                        action         : "get_connected_items",
                        nonce          : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                        rel_id         : relation_data.relId,
                        src_type_id    : relation_data.srcId,
                        dst_type_id    : relation_data.dstId,
                    };

                if (relation_data.relDir == "to_from") {
                    data.to_id = relation_data.postId;
                } else {
                    data.from_id = relation_data.postId;
                }

                jQuery.ajax({
                    url: ajaxurl,
                    dataType: "html",
                    data : data,
                    success: function (data) {
                        jQuery('#relationlist').html(data)
                    }
                });
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

            function add_relation_with_new_post () {
                var selected_relation   = jQuery("#relation_selector option:selected");
                var relation_data       = selected_relation.data();
                var new_post_title      = jQuery('#relation_src_search').val();

                var metadata_fields     = jQuery('#add_src_box .wpc_input');

                var data = {
                        action         : "add_relation_with_new_post",
                        nonce          : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                        rel_id         : relation_data.relId,
                        src_type_id    : relation_data.srcId,
                        dst_type_id    : relation_data.dstId,
                        new_post_title : new_post_title,

                        metadata     : {}
                    };
                if (relation_data.relDir == "to_from") {
                    data.from_id = relation_data.postId;
                } else {
                    data.to_id = relation_data.postId;
                }

                for (var i = metadata_fields.length - 1; i >= 0; i--) {
                    var metadata_field = jQuery(metadata_fields[i]);

                    data.metadata[metadata_field.attr('id')] = metadata_field.val();
                }

                console.log(data);

                jQuery.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    data : data,
                    success: function (data) {
                        console.log(data);
                    }
                });
            }

            function delete_relation (relation_id) {
                var data = {
                        action      : "delete_relation",
                        nonce       : "<?php echo wp_create_nonce('relations_ajax'); ?>",
                        relation_id : relation_id
                    };

                jQuery.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    data : data,
                    success: function (data) {
                        set_connected_items();
                    }
                });
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
            set_connected_items();
            jQuery('body').delegate('#relation_selector', 'change', set_selected_relation);

            jQuery('body').delegate('a#add_src_link', 'click', function(event) {
                event.preventDefault();
                add_relation ();
            });
            jQuery('body').delegate('.connected_item_delete', 'click', function (event) {
                event.preventDefault();
                var relation_id = jQuery(this).data('relation_id');
                delete_relation(relation_id);
            });
            jQuery('body').delegate('a#relation_search_add_new', 'click', function(event) {
                event.preventDefault();
                add_relation_with_new_post();
            });
            jQuery('body').delegate('a#cancel_src_link', 'click', function(event) {
                event.preventDefault();
                jQuery('#add_src_box').hide();
                jQuery('#relation_src_list').show();

                jQuery('#add_src_box').empty();
            });
            jQuery('body').delegate('a.relation_source_item', 'click', function(event) {
                event.preventDefault();
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
        add_action('wp_ajax_get_post_type_items',               array('__GenericRelationship', 'get_post_type_items_ajax'));
        add_action('wp_ajax_add_relation',                      array('__GenericRelationship', 'add_relation_ajax'));
        add_action('wp_ajax_add_relation_with_new_post',        array('__GenericRelationship', 'add_relation_ajax'));
        add_action('wp_ajax_get_connected_items',               array('__GenericRelationship', 'get_connected_items_ajax'));
        add_action('wp_ajax_delete_relation',                      array('__GenericRelationship', 'delete_relation_ajax'));

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
        _log($req);

        $from_id = isset($_REQUEST["from_id"])? $_REQUEST["from_id"] : -1;
        $to_id = isset($_REQUEST["to_id"])? $_REQUEST["to_id"] : -1;


        // if one id is missing, create a new post with name in new_post_title
        if (($from_id >=0 xor $to_id >=0) and isset($req->new_post_title)) {
          $type = $from_id ? $req->dst_type_id : $req->src_type_id;
          $id = wp_insert_post (array('post_title'=>$req->new_post_title));
          _log ("add_relation_ajax: created post $id");
          if ($from_id)
            $to_id = $id;
          else
            $from_id = $id;
        }

        $metadata = array();
        $rel_id = $req->rel_id;
        $ajax_rel_id = 'wpc_'.$rel_id;

        # this is certainly not right. let's see later what $_REQUEST looks like...
        foreach ($_REQUEST as $f=>$k) {
          if (substr($f, 0, strlen($ajax_rel_id)) == $ajax_rel_id)
            $metadata[substr($f, strlen($ajax_rel_id))] = $k;
        }

        $ret = __GenericRelationship::add_relation($to_id, $from_id, $rel_id, $metadata);

        echo json_encode($ret);

        die();
    }

    static function get_connected_items_ajax () {
      header('Content-type: text/html');

      $req = (object)$_REQUEST;

      if( empty($req->rel_id) || !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
        _log("wp_verify_nonce('".$req->nonce."', '".$req->rel_id."') failed");
        _die();
      } else
        _log("wp_verify_nonce('".$req->nonce."', '".$req->rel_id."') succeded");

      $from_id = isset($req->from_id) ? $req->from_id : -1;
      $to_id = isset($req->to_id) ? $req->to_id : -1;
      $rel_id = $req->rel_id;

      $rows = self::get_connected_items($rel_id, $to_id, $from_id);

      echo '<ul>';
      foreach ($rows as $item) {
          echo "<li> $item->relation_id $item->post_title
              <a href='#' id='relation-$item->relation_id' class='connected_item_delete' data-relation_id='$item->relation_id'>delete</a></li>\n";
      }
      echo '</ul>';
    }

    static function delete_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _log ("wp_verify_nonce($req->nonce) failed.");
            _die();
        }

        $ret = self::delete_relation($req->relation_id);

        _log("".$ret);
        echo $ret;
    }

    static function add_relation ($to_id, $from_id, $rel_id, $metadata = array()) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        if ($from_id <= 0)
          $ret->errors[] = "from_id has invalid value '$from_id'";
        else if ($to_id <= 0)
          $ret->errors[] = "to_id has invalid value '$to_id'";
        else {
          # there is a race between these two lines. hope, this does not matter. MYISAM does not support transactions...
          $stmt = $wpdb->query($wpdb->prepare ("INSERT INTO wp_wpc_relations (post_from_id, post_to_id, relationship_id) VALUES (%d, %d, %s)", $from_id, $to_id, $rel_id));
          $id = $wpdb->insert_id;

          if (count($metadata)) {
            $sql = 'INSERT INTO wp_wpc_relations_meta (relation_id, meta_key, meta_value) VALUES (%d, %s, %s);';
            foreach ($metadata as $k=>$v)
              $wpdb->query($wpdb->prepare ($sql, $id, $k, $v));
          }
        }

        return $ret;
    }

    static function get_connected_items ($rel_id, $id_from=-1, $id_to=-1) {
      global $wpdb;

      if ($id_from >= 0) {
        $id = $id_from;
        $col = 'post_from_id';
        $othercol = 'post_to_id';
      } else {
        $id = $id_to;
        $col = 'post_to_id';
        $othercol = 'post_from_id';
      }

      if (!isset($id)) {
        die ('neither id_from nor id_to set');
      }

      $sql = "SELECT wp_posts.post_title, wp_posts.ID, wp_wpc_relations.* FROM wp_wpc_relations
        JOIN wp_posts ON wp_posts.id = wp_wpc_relations.$othercol
        WHERE $col = %d AND relationship_id = %s";
      $ret = $wpdb->get_results($wpdb->prepare($sql, $id, $rel_id));

      // add metadata
      $sql = "SELECT meta_id, meta_key, meta_value FROM wp_wpc_relations_meta
        WHERE relation_id = %d";
      foreach ($ret as &$row) {
        $row->metadata = $wpdb->get_results($wpdb->prepare($sql, $row->relation_id));
      }

      return $ret;
    }

    static function delete_relation ($relation_id) {
        global $wpdb;

        // delete row itself
        $sql = 'DELETE FROM wp_wpc_relations WHERE relation_id = %d;';
        $ret = $wpdb->query($wpdb->prepare($sql, $relation_id));

        // delete metadata
        $sql = 'DELETE FROM wp_wpc_relations_meta WHERE relation_id = %d;';
        $wpdb->query($wpdb->prepare($sql, $relation_id));

        // return how many relations have been deleted (i.e. one or zero)
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
    static function get_post_type_items_ajax() {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = __GenericRelationship::get_post_type_items_for_relation($req);

        echo json_encode($ret);

        die();
    }
}

?>
