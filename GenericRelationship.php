<?php

global $wpc_content_types;
global $wpc_relationships;
$wpc_relationships = array();

abstract class GenericRelationship {
    public $id                      = "";
    public $fields                  = array();
    public $generated_fields        = array();

    public $label                   = "";
    public $label_reverse           = "";

    public $ordered                 = false;
    public $ordered_reverse         = false;

    public $post_type_from_id       = "";
    public $post_type_from          = NULL;
    public $post_type_to_id         = "";
    public $post_type_to            = NULL;
    public $table                   = NULL;
    public $helptext                = "";

    public $field_to_show_in_list   = "";
    public $field_to_put_as_class   = "";
    public $field_to_lock_relation  = "";

    function __construct () {
        global $wpc_relationships;
        global $wpc_content_types;

//  SET DEFAULTS
        if ( empty($this->id) )             $this->id               = strtolower( get_class_name($this) );

        if ( empty($this->label) )          $this->label            = $this->id;
        if ( empty($this->label_reverse) )  $this->label_reverse    = $this->label;
        if ( empty($this->table) )          $this->table            = "wp_wpc__$this->id";

        if ( !empty($this->helptext) )      $this->helptext         = $this->helptext;


        if ( !in_array( $this->post_type_from_id, get_post_types() ) ) {
            die ("in wpc relation \"$this->id\" is post_type_from not a valid wpc content_type\npost_type_from_id == \"$this->post_type_from_id\"");

            return ;
        } else {
            $this->post_type_from       = $wpc_content_types["$this->post_type_from_id"];

            $wpc_content_types[$this->post_type_from_id]->relationships[$this->id] = $this;
        }

        if ( !in_array($this->post_type_to_id, get_post_types()) ) {
            die ("in wpc relation \"$this->id\" is post_type_to not a valid wpc content_type\npost_type_from_id == \"$this->post_type_to_id\"");

            return ;
        } else {
            $this->post_type_to         = $wpc_content_types["$this->post_type_to_id"];

            $wpc_content_types[$this->post_type_to_id]->relationships[$this->id] = $this;
        }


        if (isset($wpc_ids[$this->id]) ) {
            die ("wpc relation \"$this->id\" is not unique");

            return ;
        } else {
            $wpc_relationships[$this->id] = $this;
        }
    }

    static private $is_first_metabox = false;

    protected function get_field_type ($field_key) {
        $ret = "";

        if ( !empty($this->fields[$field_key]) )
            $ret = $this->fields[$field_key]->type;

        return $ret;
    }

    static function echo_relations_metabox ($post) {
        include("metaboxes/relations_metabox.php");
    }

    function echo_item_metabox () {
        return "";
    }
    function echo_item_metabox_str () {
        ob_start();

        $this->echo_item_metabox();

        $html_str = ob_get_clean();
        $html_str = preg_replace("/(id|for|name)\=('|\")wpc_/", "$1=$2wpc_".$this->id."_", $html_str);

        $html_str = htmlspecialchars($html_str);
        $html_str = preg_replace("/\n/", "\\\n", $html_str);

        return $html_str;
    }

    static function hookup_ajax_functions () {
        add_action('wp_ajax_get_post_type_items',               array(__CLASS__, 'get_post_type_items_ajax'));
        add_action('wp_ajax_get_post_metadata',                 array(__CLASS__, 'get_post_metadata_ajax'));
        add_action('wp_ajax_add_relation',                      array(__CLASS__, 'add_relation_ajax'));
        add_action('wp_ajax_add_relation_with_new_post',        array(__CLASS__, 'add_relation_ajax'));
        add_action('wp_ajax_update_relation',                   array(__CLASS__, 'update_relation_ajax'));
        add_action('wp_ajax_update_relation_order',             array(__CLASS__, 'update_relation_order_ajax'));
        add_action('wp_ajax_get_connected_items',               array(__CLASS__, 'get_connected_items_ajax'));
        add_action('wp_ajax_delete_relation',                   array(__CLASS__, 'delete_relation_ajax'));
    }

    static function add_relation ($req) {
        global $wpdb;
        global $wpc_content_types, $wpc_relationships;

        if( empty($req) )
             $req = $_REQUEST;

        $req = (object)$req;

        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        #ButterLog::debug("add_relation", $req);

        if ( (!isset($req->from_id) xor !isset($req->to_id)) && !empty($wpc_relationships[$req->rel_id]) ) {
            $relation = $wpc_relationships[$req->rel_id];

            if ( !isset($req->to_id) ) {
                $new_post_title     = !empty($req->item_metadata) && !empty($req->item_metadata["post_title"]) ? $req->item_metadata["post_title"] : "New ".ucwords($relation->post_type_to->singular_label);
                $new_post_id = wp_insert_post (
                    array(
                        'post_type' => $relation->post_type_to_id,
                        'post_title' => $new_post_title,
                        'post_status' => ( $relation->post_type_to->auto_publish_from_rel_edit ? "publish" : "draft" )
                    )
                );

                $req->to_id     = $new_post_id;
                $req->item_id   = $new_post_id;
                $req->item_type = $relation->post_type_to_id;
            } else {
                $new_post_title     = !empty($req->item_metadata) && !empty($req->item_metadata["post_title"]) ? $req->item_metadata["post_title"] : "New ".ucwords($relation->post_type_from->singular_label);
                $new_post_id = wp_insert_post (
                    array(
                        'post_type' => $relation->post_type_from_id,
                        'post_title' => $new_post_title,
                        'post_status' => ( $relation->post_type_from->auto_publish_from_rel_edit ? "publish" : "draft" )
                    )
                );

                $req->from_id   = $new_post_id;
                $req->item_id   = $new_post_id;
                $req->item_type = $relation->post_type_from_id;
            }

           # ButterLog::debug("created post $new_post_id");
        }

        #ButterLog::debug("add_relation", $req);

        if ($req->from_id <= 0)
            $ret->errors[] = "from_id has invalid value '$req->from_id'";
        else if ($req->to_id <= 0)
            $ret->errors[] = "to_id has invalid value '$req->to_id'";
        if (empty($wpc_relationships[$req->rel_id]))
            $ret->errors[] = "rel_id has invalid value '$req->rel_id'";
        else {
            $row = array(
                'post_from_id' => $req->from_id,
                'post_to_id'   => $req->to_id
            );
            $formats = array("%d", "%d");

            if (! empty($req->relation_metadata)) {
                $req->relation_metadata = array_map(function ($value) {
                    return htmlspecialchars_decode($value, ENT_QUOTES);
                }, $req->relation_metadata);

                $formats += array_fill(2, count($req->relation_metadata), "%s");
                $row += $req->relation_metadata;
            }

            $rel = $wpc_relationships[$req->rel_id];
            if (! $wpdb->insert($rel->table, $row, $formats)) {
                    ButterLog::error("Could not insert relation: $req->rel_id with data", $row);
                    $ret["errors"][] = 'Could not insert relation';
                }

            $ret->id = $wpdb->insert_id;

            if ( !empty($req->item_metadata)) {
                $req->item_metadata = array_map(function ($value) {
                    return htmlspecialchars_decode($value, ENT_QUOTES);
                }, $req->item_metadata);

                $type = $wpc_content_types[$req->item_type];

                $type->update_post($req->item_id, array(), $req->item_metadata);
            }
        }
        return $ret;
    }
    static function add_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            ButterLog::debug("wp_verify_nonce($req->nonce) failed.");
            _die();
        }

        $ret = self::add_relation($req);

        echo json_encode($ret);

        die();
    }

    static function update_relation ($req) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        if ( empty($req) )
            $req = $_REQUEST;

        $req = (object)$req;
        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        #ButterLog::debug("update_relation ", $req);

        if ($req->from_id <= 0) {
            $ret->errors[] = "from_id has invalid value '$req->from_id'";
        } else if ($req->to_id <= 0) {
            $ret->errors[] = "to_id has invalid value '$req->to_id'";
        } else if (empty($wpc_relationships[$req->rel_id])) {
            $ret->errors[] = "rel_id has invalid value '$req->rel_id'";
        } else if (empty($req->id)) {
            $ret->errors[] = "id has invalid value";
        } else if ( empty($req->relation_metadata) && empty($req->item_metadata) ) {
            $ret->errors[] = "cannot update without anything to update.";
        } else {
            $rel = $wpc_relationships[$req->rel_id];

            if ( !empty($req->relation_metadata) ) {
                $req->relation_metadata = array_map(function ($value) {
                    return htmlspecialchars_decode($value, ENT_QUOTES);
                }, $req->relation_metadata);

                if ( $wpdb->update($rel->table, /*data*/$req->relation_metadata, /*where*/array("id" => $req->id), /*formats*/"%s", array("%d")) === FALSE ) {
                    ButterLog::error("Could not update relation: $req->rel_id with data", $req->relation_metadata);
                    $ret["errors"][] = 'Could not update relation';
                }
            }

            if (! empty($req->item_metadata)) {
                $req->item_metadata = array_map(function ($value) {
                    return htmlspecialchars_decode($value, ENT_QUOTES);
                }, $req->item_metadata);

                $type = $wpc_content_types[$req->item_type];

                $type->update_post($req->item_id, array(), $req->item_metadata);
            }
        }

        return $ret;
    }
    static function update_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            ButterLog::info("wp_verify_nonce($req->nonce) failed.");
            _die();
        }

        $ret = self::update_relation($req);

        echo json_encode($ret);

        die();
    }
    static function update_relation_order ($req) {
        global $wpdb;
        global $wpc_relationships;
        global $wpc_content_types;

        if ( empty($req) )
            $req = $_REQUEST;

        $rel = $wpc_relationships[$req->rel_id];
        $req = (object)$req;
        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
        );

        if ( !empty($req->order) ) {
            foreach ($req->order as $order) {
                $sql    = "UPDATE `$rel->table` SET `order` = %d WHERE `id` = %d;";
                $query  = $wpdb->prepare($sql, $order['pos'], $order['id']);
                $wpdb->query( $query );
            }
        }

        #ButterLog::debug("update_relation ", $req);

        return $ret;
    }

    static function update_relation_order_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            ButterLog::info("wp_verify_nonce($req->nonce) failed.");
            _die();
        }

        $ret = self::update_relation_order($req);

        echo json_encode($ret);

        die();
    }

    static function get_connected_items ($req) {
        global $wpdb;
        global $wpc_relationships;

        $ret = (object)array(
                     "errors" => array (),
                     "status" => array (),
                    "results" => array (),
                );

        $ordered = false;

        if ( empty($req->rel_id) ) {
            $ret->errors[] = "rel_id was not specified";
        } else {
            $rel = $wpc_relationships[$req->rel_id];

            if ( !empty($req->from_id) ) {
                $id         = $req->from_id;
                $col        = 'post_from_id';
                $othercol   = 'post_to_id';
                $othertable = $rel->post_type_to->table;
                $ordered    = $rel->ordered == true;
            } else if ( !empty($req->to_id) ) {
                $id         = $req->to_id;
                $col        = 'post_to_id';
                $othercol   = 'post_from_id';
                $othertable = $rel->post_type_from->table;
                $ordered    = $rel->ordered_reverse == true;
            } else {
                $ret->errors[] = "neither from_id nor to_id were specified";
            }
        }

        if ( !empty($id) ) {
            $sql = "SELECT $rel->table.* FROM $rel->table ".
            "INNER JOIN wp_posts on wp_posts.ID = $rel->table.$othercol ".
            "WHERE $col = %d";

            if ( $ordered )
                $sql .= " ORDER BY `order`, `id`";
            else
                $sql .= " ORDER BY `id`";

            $sql_result = $wpdb->get_results($wpdb->prepare($sql, $id));

            foreach ($sql_result as $relation_row) {
                $row_ret = new stdClass();
                $row_ret->item_metadata     = array();
                $row_ret->relation_metadata = array();

                foreach ($relation_row as $key => $value) if ($key != 'post_from_id' && $key != 'post_to_id') {
                    $row_ret->relation_metadata[$key] = $value;
                }

                $row_ret->id            = $relation_row->id;
                $row_ret->post_from_id  = $relation_row->post_from_id;
                $row_ret->post_to_id    = $relation_row->post_to_id;

                $other_record = the_record($row_ret->$othercol);

                $row_ret->item_metadata["post_title"]   = $other_record->post_title;
                $row_ret->item_metadata["post_status"]  = $other_record->post_status;

                $field_keys = explode(",", $rel->field_to_show_in_list);

                foreach ($field_keys as $field_key) {
                    if (substr($field_key, 0, 11) == "other-item-") {
                        $field_key = substr($field_key, 11);

                        $row_ret->item_metadata[$field_key] = $other_record->get($field_key);
                    }
                }

                array_push($ret->results, $row_ret);
            }
        }

        return $ret;
    }
    static function get_connected_items_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = self::get_connected_items($req);

        echo json_encode($ret);

        die();
    }

    static function get_post_metadata ($req) {
        global $wpdb, $wpc_content_types;

        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
            );

        if ( !isset($req->post_id) ) {
            $ret->errors[] = "post_id was not specified.";
        } else {
            $post_id        = $req->post_id;
            $post_type      = get_post_type($post_id);
            $content_type   = $wpc_content_types[$post_type];

            $sql = "SELECT * FROM $content_type->table WHERE post_id = %d";

            $sql_result = $wpdb->get_results($wpdb->prepare($sql, $post_id));

            foreach ($sql_result as &$item_metadata_row) {
                $item_metadata = array();

                foreach ($item_metadata_row as $key => $value){
                    $ret->results[$key] = $value;
                }
            }
        }

        return $ret;
    }
    static function get_post_metadata_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = self::get_post_metadata($req);

        echo json_encode($ret);

        die();
    }
    static function delete_relation ($req) {
        global $wpdb, $wpc_relationships;

        $ret = (object)array(
             "errors" => array (),
             "status" => array (),
            "results" => array (),
            );

        if (! isset($req->rel_id) || !isset($wpc_relationships[$req->rel_id])) {
            $ret->errors[] = "rel_id was not specified or is not registered.";
        } else if (! isset($req->id)) {
            $ret->errors[] = "id was not specified";
        } else {
            $rel = $wpc_relationships[$req->rel_id];
            $stmt = "DELETE FROM $rel->table WHERE id = %d;";
            if (! $wpdb->query($wpdb->prepare($stmt, $req->id))) {
                ButterLog::error("Could not delete id $req->id in table $req->table.");
                $ret->errors[] = 'Could not delete relation.';
            }
        }

        return $ret;
    }
    static function delete_relation_ajax () {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;

        if ( !wp_verify_nonce($req->nonce, 'relations_ajax') ) {
            _ping();
            ButterLog::info("wp_verify_nonce($req->nonce) failed.");
            _die();
        }

        $ret = self::delete_relation($req);

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
                    " WHERE $wpdb->posts.post_type  = %s AND $wpdb->posts.post_status NOT IN ('trash', 'auto-draft') \n".
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

                $ret->status['result_type'] = 'search';

                if ( empty($req->filter) ) {
                    $count = wp_count_posts($req->post_type);

                    if ($count->publish > 25) {
                        $req->order_by              = "date";
                        $req->order                 = "desc";
                        $ret->status['result_type'] = 'latest';
                    } else {
                        $req->order_by              = "title";
                        $req->order                 = "asc";
                        $ret->status['result_type'] = 'available';
                    }
                } else if ( !isset($req->order_by) )
                    $req->order_by  = "NULL";

                if ( ( isset($req->order_by) && in_array ($req->order_by, array ("id",  "title", "date", "NULL")) )
                  && ( empty($req->order)    || in_array ($req->order,    array ("asc", "desc")) ) ){
                    $req->order_by  = str_replace(array("id",  "title", "date", "NULL"), array("$wpdb->posts.ID",  "$wpdb->posts.post_title",  "$wpdb->posts.post_date", "NULL"), $req->order_by);
                    $req->order     = ( isset($req->order) && $req->order == "desc" ) ? "DESC" : "ASC";

                    $prepared_sql_order  = $wpdb->prepare("ORDER BY %s %s ", $req->order_by, $req->order);
                }

                $available_count    = $wpdb->get_var    ( "SELECT COUNT(*) $prepared_sql_filter $prepared_sql_like" );
                $results            = $wpdb->get_results( "SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_type
$prepared_sql_filter
$prepared_sql_like
$prepared_sql_order
$prepared_sql_limit" );

                $ret->status['available_results']   = $available_count;
                $ret->status['returned_results']    = count($results);

                $ret->results       = $results;
            } else {
                $ret->errors[]      = "Specified post_type '$req->post_type' is invalid.";
            }
        } else {
            $ret->errors[] = "No post_type was specified";
        }

        return $ret;
    }
    static function get_post_type_items_ajax() {
        header('Content-type: text/javascript');

        $req = (object)$_REQUEST;
        $ret = self::get_post_type_items($req);

        echo json_encode($ret);

        die();
    }

    static public function echo_relationship_tabs($rel_ids, $post) {
        global $wpc_relationships;

        if ( !is_array($rel_ids) )
            $rel_ids = explode(",", $rel_ids);

        $rels = array();

        foreach ($rel_ids as $rel_id) {
            $reverse = substr($rel_id, 0, 8) == "reverse_";

            if ($reverse) $rel_id_clean = substr($rel_id, 8);
            else $rel_id_clean = $rel_id;

            if ( !empty($wpc_relationships[$rel_id_clean]) ) {
                $rels[$rel_id] = $wpc_relationships[$rel_id_clean];
            }
        }


        if ( !empty($rels) ) { ?>
            <div class="wpc_form_tabs">
                <ul class="wpc_form_tabs_header"><?php foreach ($rels as $rel_id => $rel): ?>
                    <li><a href="#tab_rel_<?php echo $rel_id ?>"><?php echo $rel->label; ?></a></li>
                <?php endforeach ?>
                </ul>
                <?php foreach ($rels as $rel_id => $rel){
                    $reverse = substr($rel_id, 0, 8) == "reverse_";

                    if ($reverse) $rel_id_clean = substr($rel_id, 8);
                    else $rel_id_clean = $rel_id;
                ?>
                    <div id="tab_rel_<?php echo $rel_id ?>">
                        <div class="" id="rel_<?php echo $rel_id ?>">
                            <div class="wpc_form_row">
                                <div class="wpc_form_row"><?php
                                    $rel->echo_relationship($post, $reverse);
                                ?></div>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
                <?php } ?>
            </div>
        <?php }

    }

    function echo_relationship($post, $reverse_direction){
        global $wpc_content_types;

        $relID = ($reverse_direction ? "reverse_" : "") . $this->id;

        if (GenericRelationship::$is_first_metabox == false){
            GenericRelationship::$is_first_metabox = true; ?>
        <script type="text/javascript" charset="utf-8">
            var admin_url_wpspin_light  = "<?php echo admin_url('images/wpspin_light.gif'); ?>";
            var admin_url_post_php      = "<?php echo admin_url('post.php'); ?>";
            var nonce_relations_ajax    = "<?php echo wp_create_nonce('relations_ajax'); ?>";

            relations_data          = {};

            jQuery(document).ready(function(){
                jQuery(".wpc_form_tabs").tabs();

                for (var relation_id in relations_data) {
                    init_relation(relations_data[relation_id]);
                };
            });
        </script>
        <?php } ?>

        <div class ="relation_edit_box <?php echo $relID ?>">
            <div class="relation_connected_box" style="display: block;">
                <table class="relation_connected_list_header wp-list-table widefat fixed posts">
                    <thead>
                        <tr><th>
                            <?php echo $reverse_direction ? $this->label_reverse : $this->label; ?>

                            <div class="relation_buttons_box">
                                <a class="button relation_connect_existing_search" href='#'>add</a>
                            </div>
                        </th></tr>
                    </thead>
                </table>
                <div class="relation_table_container">
                    <table class="relation_connected_list wp-list-table widefat fixed posts">
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="relation_add_search_box hidden" style="display: none;">
                <table class="relation_src_list_header wp-list-table widefat fixed posts">
                    <thead>
                        <tr><th>
                            Search results
                            <div class="relation_buttons_box relation_connect_existing_search_buttons_box">
                                <label>search</label>
                                <input type="text" class="wpc_input_text relation_src_search"/>

                                <a class="relation_connect_new_input button" href='#'>add new</a>
                                <a class="button relation_connect_existing_search_cancel" href='#'>cancel</a>
                            </div>
                        </th></tr>
                    </thead>
                </table>
                <div class="relation_table_container">
                    <table class="relation_src_list wp-list-table widefat fixed posts">
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="relation_edit_connected_box hidden" style="display: none;">
                <div class="relation_edit_connected_metadata_box"></div>

                <div class="relation_edit_connected_buttons_box relation_buttons_box">
                    <div class="relation_buttons_box_bottom">
                        <a class="relation_edit_connected_cancel button" href='#'>cancel</a>
                        <a class="relation_edit_connected_delete button" href='#'>remove</a>
                        <a class="relation_edit_connected_update button-primary" href='#'>save</a>
                    </div>
                </div>
            </div>

            <div class="relation_connect_existing_box hidden" style="display: none;">
                <div class="relation_connect_existing_metadata_box"></div>

                <div class="relation_connect_existing_buttons_box relation_buttons_box">
                    <div class="relation_buttons_box_bottom">
                        <a class="relation_connect_existing_cancel button" href='#'>cancel</a>
                        <a class="relation_connect_existing_add button-primary" href='#'>add</a>
                    </div>
                </div>
            </div>

            <div class="relation_connect_new_box hidden" style="display: none;">
                <div class="relation_connect_new_metadata_box"></div>

                <div class="relation_connect_new_buttons_box relation_buttons_box">
                    <div class="relation_buttons_box_bottom">
                        <a class="relation_connect_new_cancel button" href='#'>cancel</a>
                        <a class="relation_connect_new_add button-primary" href='#'>add</a>
                    </div>
                </div>
            </div>

            <div class="status-update" style="display:none"></div>
        </div>

        <script type="text/javascript" charset="utf-8">
            relations_data["<?php echo $relID ?>"] = {
                relId                   : "<?php echo $relID; ?>",
                relIdClean              : "<?php echo $this->id; ?>",
                postId                  : "<?php echo $post->ID; ?>",
                ordered                 : "<?php echo ($this->ordered && !$this->ordered_reverse) ? 'true' : 'false'; ?>",
                ordered_reverse         : "<?php echo (!$this->ordered && $this->ordered_reverse) ? 'true' : 'false'; ?>",
                label                   : "<?php echo $reverse_direction ? $this->label_reverse : $this->label; ?>",
                toId                    : "<?php echo $this->post_type_to_id; ?>",
                toLabel                 : "<?php echo $this->post_type_to->label ?>",
                toSingularLabel         : "<?php echo $this->post_type_to->singular_label ?>",
                fromId                  : "<?php echo $this->post_type_from_id; ?>",
                fromLabel               : "<?php echo $this->post_type_from->label ?>",
                fromSingularLabel       : "<?php echo $this->post_type_from->singular_label ?>",
                relDir                  : "<?php echo $reverse_direction ? "from_to" : "to_from"; ?>",
                fieldToLockRelation     : "<?php echo $this->field_to_lock_relation; ?>",
                fieldsToPutAsClass      : "<?php echo $this->field_to_put_as_class; ?>",
                fieldsToShowInList      : "<?php echo $this->field_to_show_in_list; ?>",
                relationEditBox         : "<?php echo $this->echo_item_metabox_str($post); ?>",
                itemUpdateEditBox       : "<?php echo $reverse_direction ? $this->post_type_from->echo_update_relation_item_metabox_str() : $this->post_type_to->echo_update_relation_item_metabox_str(); ?>",
                itemNewEditBox          : "<?php echo $reverse_direction ? $this->post_type_from->echo_new_relation_item_metabox_str() : $this->post_type_to->echo_new_relation_item_metabox_str(); ?>"
            }

        </script>
    <?php }
}

?>
