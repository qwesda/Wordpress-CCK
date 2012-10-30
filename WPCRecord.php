<?php

/**
 * The base class for records of a content type.
 */
abstract class WPCRecord extends WPCData {

    /*
     * the post's id
     */
    protected $id = null;

    protected $type;

    /**
     * constructor for a Record.
     *
     * If id is null, create a new record in the database.
     *
     * If $post and $meta are set, use them (for when they are already fetched one way or another).
     * Both must be an associative array.
     */
    protected function __construct($id=null, $post=null, $meta=null) {
        global $wpc_content_types;
        $this->type = $wpc_content_types[$this->typeslug];

        // set meta corresponds to wpc keys,
        // data to wp keys that are not managed by wpc
        $this->meta_keys = array_keys($this->type->fields);
        $this->data_keys = array_diff(array('post_author', 'post_date',
            'post_date_gmt', 'post_content', 'post_content_filtered',
            'post_title', 'post_excerpt', 'post_status', 'post_type',
            'comment_count', 'comment_status', 'ping_status', 'post_password',
            'post_name', 'to_ping', 'pinged', 'post_modified',
            'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type',
            'guid'), $this->meta_keys);

        if ($id === null && ! empty($post))
            $id = $post["ID"];

        $this->id = $id;

        parent::__construct($post, $meta);
    }

    /**
     * returns a new object of the right type.
     */
    static function new_record($id=null, $p=null, $m=null, $type=null) {
        // post might be an object. cast to associative array.
        $p = (array) $p;

        // deduct $id and $type if possible
        if ($id === null && ! empty($p))
            $id = $p["ID"];

        if (! $p && $id !== null)
            $p = get_post($id, 'ARRAY_A');

        if (! $type) {
            if (! isset($p['post_type'])) {
                ButterLog::error('Cannot create new record without type.');
                return;
            }
            $type = $p['post_type'];
        }

        $classname = ucfirst($type)."Record";
        self::make_specific_class($classname, $type);

        // ButterLog::debug("new_record(id: $id, type: $type)");

        return new $classname($id, $p, $m);
    }

    protected function connected_for_type($other_type, $reverse) {
        return WPCRelationCollection::relations_for_types($this->typeslug, $reverse, $other_type, $this->id);
    }
    protected function connected_by_id($db_relationslug, $reverse) {
        return WPCRelationCollection::relations_by_id($db_relationslug, $reverse, $this->id);
    }

    protected function exists_connected($other_type) {
        // just say yes.
        return true;
    }

    function delete() {
        global $wpc_content_type;

        $this->data = array();
        $this->meta = array();

        if ($this->id === null)
            return;

        // do not return self, but whether it worked or not.
        return $this->type->delete_post($this->id);
    }

    function commit () {
        global $wpc_content_types;

        if ($this->id === null) {
            $this->id = $this->type->create_post($this->to_set_data);
            $this->to_set_data = array();

            // load new data (assume meta will not be set)
            $this->load_data();
        }

        if (! empty($this->data_to_set) || ! empty($this->meta_to_set)) {
            $post_data = array('ID' => $this->id) + $this->data_to_set;
            wp_update_post($post_data);

            $this->data_to_set = array();
        }

        if (! empty($this->meta_to_set)) {
            $this->type->update_post($this->id, array(), $this->meta_to_set);

            $this->meta_to_set = array();
        }
        return $this;
    }

    protected function load_meta() {
        global $wpdb, $wpc_content_types;

        if ($this->id === null)
            return;

        $table = $this->type->table;
        $wpid_col = $this->type->wpid_col;
        $stmt = $wpdb->prepare("SELECT * FROM $table WHERE $wpid_col = %d;",
            $this->id);
        $this->meta = $wpdb->get_row($stmt, 'ARRAY_A');
    }
    protected function load_data() {
        if ($this->id === null)
            return;
        $this->data = get_post($this->id, 'ARRAY_A');
    }
}

// WP-style maker for the record
function the_record ($id = null) {
    global $post;

    if (!empty($id)) {
        $the_record = WPCRecord::new_record($id);

        return $the_record;
    }

    if (!empty($post)) {
        $the_record = WPCRecord::new_record($post->ID, $post);

        return $the_record;
    }

    return null;
}

?>
