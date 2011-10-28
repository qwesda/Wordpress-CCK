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


/**
 * REMAKRS:
 * #qwesda: formated fields should be caching since they are likly to be called twice - once with empty() to check if it should be displayed, and than to display it - implemented it! up for discussion ...
 * #qwesda: the records themself migh also chache - not sure about that though, but they could be stored in a hash ... getting the postmeta, etc might be a lot of work if the templates get very complex
 */

abstract class GenericRecord {
    protected $typeslug = '';   #qwesda: maybe post_type is better as a name "type" and "slug" are two different things - but maybe just i don't know what you mean

    protected $id = null;
    protected $post = array();
    protected $postrelations = array();
    protected $postmeta = array();

    protected $formatted_string_cache = array();

    function __construct($id=null) {
        $this->id = $id;

        // set typeslug to the lowercased classname, if not set
        if (empty($this->typeslug))
            $this->typeslug= strtolower(get_class($this));

        if (!empty($this->id)) {
            // get $post as hash for consistency reasons
            $this->post = get_post($this->id, 'ARRAY_A');
            $this->postmeta = get_post_custom($this->id);
        }
    }

    function __get($attribute) {
        #qwesda: return multiple post_meta with same key as array only if requested with prefix "all_" and first value otherwise
        if (strpos($attribute, "all_") === 0 && isset($this->postmeta[$attribute]))
            return $this->postmeta[$attribute];

        if (!empty($this->postmeta[$attribute]))
            return $this->postmeta[$attribute][0];

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

        // return empty string for non-existing thingies
        return "";
    }

    function __set($attribute, $val) {
        // TODO: verify, really save
        if (isset($this->postmeta[$attribute])) {
            $this->postmeta[$attribute] = $arg;
            // and save it!
        } else {
            $this->post[$attribute] = $val;
            // and save it!
        }
    }

    function __isset($attribute) {
        return (isset($this->postmeta[$attribute]) || isset($this->post[$attribute]));
    }

    protected function get_connected($type) {
        // TODO: implement
        return array();
    }

    protected function formatted_string ($key) {
        $value = '';

        if (!empty($this->postmeta[$key]))
            $value = $this->postmeta[$key][0];

        else if (isset($this->post[$key]))
            $value = $this->post[$key];

        // check for the following filters, apply the first
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
