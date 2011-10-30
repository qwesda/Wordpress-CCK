<?php

global $wpc_relationships;
$wpc_relationships = array();

abstract class GenericRelationship {
    public $id                      = "";
    public $fields                  = NULL;

    public $label                   = "";

    public $post_type_from_id       = "";
    public $post_type_from          = NULL;
    public $post_type_to_id         = "";
    public $post_type_to            = NULL;

    public $field_to_show_in_list	= "";

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
		include("metaboxes/relations_metabox.php");
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
        add_action('wp_ajax_get_post_type_items',               array(__CLASS__, 'get_post_type_items_ajax'));
        add_action('wp_ajax_add_relation',                      array(__CLASS__, 'add_relation_ajax'));
        add_action('wp_ajax_add_relation_with_new_post',        array(__CLASS__, 'add_relation_ajax'));
        add_action('wp_ajax_update_relation',                   array(__CLASS__, 'update_relation_ajax'));
        add_action('wp_ajax_get_connected_items',               array(__CLASS__, 'get_connected_items_ajax'));
        add_action('wp_ajax_delete_relation',                   array(__CLASS__, 'delete_relation_ajax'));
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

        $ret = self::add_relation($req);

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

        $ret = self::update_relation($req);

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
            $sql = "SELECT $wpdb->posts.post_title, $wpdb->posts.ID, wp_wpc_relations.* FROM wp_wpc_relations
              JOIN $wpdb->posts ON $wpdb->posts.id = wp_wpc_relations.$othercol
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
        $ret = self::get_connected_items($req);

        echo json_encode($ret);

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
            $ret->errors[] = "relation_id was not specified or is not a valid id";
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
        $ret = self::get_post_type_items($req);

        echo json_encode($ret);

        die();
    }
}

?>
