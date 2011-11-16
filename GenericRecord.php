<?php
/**
 * The base class for records of a content type.
 *
 * It applies a filter of the form "wpc_format_$type_$key" when a
 * property is asked for. If this filter is not set, it simply calls
 * "wpc_format_$type" or simply "wpc_format".
 * For qtranslate to handle quicktags, etc. you can just add
 * "qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage".
 */


abstract class GenericRecord {
    /*
     * the slug of the custom post type. used for looking up the connected relations.
     */
    protected $typeslug = '';

    /*
     * the post's id
     */
    protected $id = null;
    /*
     * the post's data as returned by get_post with 'ARRAY_A'.
     */
    protected $post;
    /*
     * the post's meta data as returned by wp_post_custom
     */
    protected $meta;

    /*
     * an associative array. keys are the connected types.
     */
    protected $relations = array();

    protected $formatted_string_cache = array();

    /**
     * constructor for a Record.
     *
     * If $post and $meta are set, use them (for when they are already fetched one way or another).
     * Both must be an associative array.
     */
    function __construct($id=null, $post=null, $meta=null) {
        if ($id === null) {
            _log("No id given!");
            throw new Exception("Cannot construct ".get_class().". Id is not set.");
        }

        $this->id = $id;
        $this->post = $post;
        $this->meta = $meta;

        // set typeslug to the lowercased classname, if not set
        if (empty($this->typeslug))
            $this->typeslug= strtolower(get_class($this));
    }

    /**
     * returns a new object of the right type.
     */
    static function new_type($id=null, $type=null, $p=null, $m=null) {
        if (! (($id && $type) || $p)) {
            // XXX: _error needed;
            _log("Cannot get new record with neither post nor id or type set.");
            return;
        }

        // post might be an object. cast to associative array.
        $p = (array) $p;

        // deduct $id and $type from $p
        if (! $id)
            $id = $p["ID"];
        if (! $type)
            $type = $p["post_type"];

        $classname = ucfirst($type)."Record";
        return new $classname($id, $p, $m);
    }

    /**
     * return multiple post_meta with same key as array if requested with prefix "all_" and first value otherwise.
     * with prefix "formatted_" return a formatted string. see above for the filters to use.
     * with prefix "connected_" return the connected items of a specific type.
     */
    function __get($attribute) {
        if (empty($this->post)) {
            // get $post as hash for consistency reasons
            $this->post = get_post($this->id, 'ARRAY_A');
        }
        if (empty($this->meta))
            $this->meta = get_post_custom($this->id);

        if (strpos($attribute, "all_") === 0 && isset($this->meta[$attribute]))
            return $this->meta[$attribute];

        if (!empty($this->meta[$attribute]))
            return $this->meta[$attribute][0];

        if (isset($this->post[$attribute]))
            return $this->post[$attribute];

        if (strpos($attribute, "connected_") === 0)
            return $this->get_connected(substr($attribute, strlen("connected_")));

        if (strpos($attribute, "formatted_") === 0) {
            $attribute_key = substr($attribute, strlen("formatted_"));

            if ( isset($this->formatted_string_cache[$attribute_key]) ) {
                _log ("serving cached: $attribute");

                return $this->formatted_string_cache[$attribute_key];
            }

            return $this->formatted_string($attribute_key);
        }

        // return empty string for non-existing attributes.
        _log(get_class()." does not have attribute '$attribute'.");
        return "";
    }

    function __set($attribute, $val) {
        // TODO: verify, really save
        if (isset($this->meta[$attribute])) {
            $this->meta[$attribute] = $arg;
            // and save it!
        } else {
            $this->post[$attribute] = $val;
            // and save it!
        }
    }

    function __isset($attribute) {
      if (! isset($this->post))
        // get $post as hash for consistency reasons
        $this->post = get_post($this->id, 'ARRAY_A');
      if (! isset($this->meta))
        $this->meta = get_post_custom($this->id);
      return isset($this->post[$attribute]) || isset($this->meta["$attribute"]);
    }

    protected function get_connected($other_type) {
        if (! isset($this->relations[$other_type]))
            $this->relations[$other_type] = GenericRelationRecords::relations_for_types($this->typeslug, $other_type, $this->id);

        return $this->relations[$other_type];

    }

    protected function formatted_string ($key) {
        $value = '';

        if (!empty($this->meta[$key]))
            $value = $this->meta[$key][0];

        else if (isset($this->post[$key]))
            $value = $this->post[$key];

        // check for the following filters, apply the first that exists.
        $filters = array("wpc_format_".$this->typeslug."_$key",
            "wpc_format_$this->typeslug",
            "wpc_format");

        foreach ($filters as $filter)
            if (has_filter($filter)) {
                $value = apply_filters($filter, $value, $this);

                $this->formatted_string_cache[$key] = $value;
                break;
            }

        return $value;
    }

    static function make_generic_record_class ($name, $typeslug="") {
        if (class_exists($name))
            return;

        if ($typeslug == "")
            $typeslug = strtolower($name);

        $classdef = "class $name extends ".__CLASS__."{
                protected \$typeslug = '$typeslug';
            }";

        $f = eval($classdef);
    }
}

// WP-style maker for the record
function the_record () {
    global $post;

    $the_record = GenericRecord::new_type($post->ID, $post->post_type, $post);

    return $the_record;
}

?>
