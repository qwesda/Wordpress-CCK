<?php

require_once('WPCData.php');

/**
 * The base class for records of a content type.
 */
abstract class WPCRecord extends WPCData {

    /*
     * the post's id
     */
    protected $id = null;

    /**
     * constructor for a Record.
     *
     * If $post and $meta are set, use them (for when they are already fetched one way or another).
     * Both must be an associative array.
     */
    function __construct($id=null, $post=null, $meta=null) {
        if ($id === null && $post === null) {
            _log(get_class($this).": Neither id nor post given!");
            throw new Exception("Cannot construct ".get_class($this).". Id and post are not set.");
        }

        if ($id === null)
            $id = $post["ID"];
        $this->id = $id;

        parent::__construct($post, $meta);
    }

    /**
     * returns a new object of the right type.
     */
    static function new_record($id=null, $p=null, $m=null, $type=null) {
        if (! ($id || $p)) {
            // XXX: _error needed;
            _log("Cannot get new record with neither post nor id set.");
            return;
        }

        // post might be an object. cast to associative array.
        $p = (array) $p;

        // deduct $id and $type
        if (! $id)
            $id = $p["ID"];
        if (! $type) {
            if (! $p)
                $p = get_post($id, 'ARRAY_A');
            $type = $p["post_type"];
        }

        $classname = ucfirst($type)."Record";
        self::make_specific_class($classname, $type);

        return new $classname($id, $p, $m);
    }

    protected function connected_for_type($other_type) {
        return WPCRelationCollection::relations_for_types($this->typeslug, $other_type, $this->id);
    }
    protected function exists_connected($other_type) {
        // just say yes.
        return true;
    }

    protected function load_meta() {
        $this->meta = get_post_custom($this->id);
    }
    protected function load_data() {
        $this->post = get_post($this->id, 'ARRAY_A');
    }
}

// WP-style maker for the record
function the_record () {
    global $post;

    $the_record = WPCRecord::new_record($post->ID, $post);

    return $the_record;
}

?>
