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
        $this->data_keys = array_diff(GenericContentType::$wp_keys,
            $this->meta_keys);

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

    function commit ($write_ro = false) {
        global $wpc_content_types;

        if ($this->id === null) {
            $this->id = $this->type->create_post($this->data_to_update,
                $this->meta_to_update, $write_ro);
            $this->data_to_update = array();
            $this->meta_to_update = array();
        } else {
            if (! empty($this->data_to_update)) {
                $post_data = array('ID' => $this->id) + $this->data_to_update;
                wp_update_post($post_data);

                $this->data_to_update = array();
            }

            if (! empty($this->meta_to_update)) {
                $this->type->update_post($this->id, array(),
                    $this->meta_to_update, $write_ro);

                $this->meta_to_update = array();
            }
        }

        // invalidate record
        $this->data = null;
        $this->meta = null;

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
