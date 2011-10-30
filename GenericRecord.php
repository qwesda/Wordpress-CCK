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
    protected $post = array();
    /*
     * the post's meta data as returned by wp_post_custom
     */
    protected $meta = array();

    /*
     * an associative array. keys are the connected types.
     */
    protected $relations = array();

    protected $formatted_string_cache = array();

    function __construct($id=null) {
        $this->id = $id;

        // set typeslug to the lowercased classname, if not set
        if (empty($this->typeslug))
            $this->typeslug= strtolower(get_class($this));

        if (!empty($this->id)) {
            // get $post as hash for consistency reasons
            $this->post = get_post($this->id, 'ARRAY_A');
            $this->meta = get_post_custom($this->id);
        }
    }

    /**
     * return multiple post_meta with same key as array if requested with prefix "all_" and first value otherwise.
     * with prefix "formatted_" return a formatted string. see above for the filters to use.
     * with prefix "connected_" return the connected items of a specific type.
     */
    function __get($attribute) {
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
        return (isset($this->meta[$attribute]) || isset($this->post[$attribute]));
    }

    protected function get_connected($other_type) {
        if (! isset($relations[$other_type]))
            $relations[$other_type] = GenericRelationRecords::relations_for_types($this->typeslug, $other_type, $this->id);

        return $relations[$other_type];

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

        $classdef = "class $name extends GenericRecord {
                protected \$typeslug = '$typeslug';
            }";

        $f = eval($classdef);
    }
}

// WP-style maker for the record
function the_record () {
    global $post;

    $class_name = ucfirst($post->post_type)."Record";
    $the_record = new $class_name($post->ID);

    return $the_record;
}

?>
