<?php

/**
 * The base class for generic records or relations
 *
 * It applies a filter of the form "wpc_format_$type_$key" when a
 * property is asked for. If this filter is not set, it simply calls
 * "wpc_format_$type" or simply "wpc_format".
 * For qtranslate to handle quicktags, etc. you can just add
 * "qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage".
 */
abstract class WPCData {

    /**
     * a short name for the type of the data
     */
    protected $typeslug;

    /*
     * the data as associative array
     */
    protected $data;

    /*
     * the meta data as associative array
     */
    protected $meta;

    /*
     * an associative array. keys are the connected types.
     */
    protected $connected_cache = array();

    protected $formatted_string_cache = array();

    /**
     * constructor for a Record.
     *
     * If $data and $meta are set, use them (for when they are already fetched one way or another).
     * Both must be an associative array.
     */
    function __construct($data=null, $meta=null) {
        $this->data = $data;
        $this->meta = $meta;
    }

    /**
     * lookup $key in data, meta data or connected things.
     *
     * return multiple meta values with the same key as array if requested with prefix "all_" and first value otherwise.
     * with prefix "formatted_" return a formatted string. see above for the filters to use.
     * with prefix "connected_" return the connected items of a specific type.
     */
    function __get($attribute) {
        if (empty($this->data)) {
            $this->load_data();
        }
        if (empty($this->meta))
            $this->load_meta();

        if (strpos($attribute, "all_") === 0 && isset($this->meta[$attribute]))
            return $this->meta[$attribute];

        if (isset($this->meta[$attribute])) {
            if (is_array($this->meta[$attribute]) && ! empty($this->meta[$attribute]))
                return $this->meta[$attribute][0];
            return $this->meta[$attribute];
        }

        if (isset($this->data[$attribute]))
            return $this->data[$attribute];

        if (strpos($attribute, "connected_") === 0)
            return $this->get_connected(substr($attribute, strlen("connected_")));

        if (strpos($attribute, "formatted_") === 0) {
            $attribute_key = substr($attribute, strlen("formatted_"));

            return $this->formatted_string($attribute_key);
        }

        // return empty string for non-existing attributes.
     #   _log(get_class($this)." does not have attribute '$attribute'.");
        return "";
    }

    function __isset($attribute) {
        if (! isset($this->data))
            $this->load_data();
        if (! isset($this->meta))
            $this->load_meta();
        if (isset($this->data[$attribute]) || isset($this->meta["$attribute"]))
            return true;

        if (strpos($attribute, "connected_") === 0
            && $this->exist_connected(substr($attribute, strlen("connected_"))))
            return true;

        if (strpos($attribute, "all_") === 0)
            return isset($this->meta["$attribute"]);

        if (strpos($attribute, "formatted_") === 0)
            return (isset($this->data[$attribute]) || isset($this->meta["$attribute"]));

        return false;
    }

    protected function load_data() {}
    protected function load_meta() {}

    protected function get_connected($other_type) {
        if (! isset($this->connected_cache[$other_type])) {
            $connection = $this->connected_for_type($other_type);
            if ($connection)
                $this->connected_cache[$other_type] = $this->connected_for_type($other_type);
            else {
                _log("No connected ".$other_type."s found for $this->typeslug.");
                return;
            }
        }

        return $this->connected_cache[$other_type];
    }
    /**
     * checks, whether there exist connected thingies
     *
     * always returns false. Overwrite for connected thingies.
     */
    protected function exist_connected($other_type) {
        return false;
    }
    /**
     * returns connected thingies of type $other_type.
     *
     * always returns null, Overwrite for connected types.
     */
    protected function connected_for_type($other_type) {
        return null;
    }

    protected function formatted_string ($key) {
        // shortcur if it is already cached
        if (isset($this->formatted_string_cache[$key]))
            return $this->formatted_string_cache[$key];

        // default to empty string
        $value = '';

        if (!empty($this->meta[$key]))
            $value = $this->meta[$key][0];

        else if (isset($this->data[$key]))
            $value = $this->data[$key];

        // apply the following filters in order
        $filters = array("wpc_format_".$this->typeslug."_$key",
            "wpc_format_$this->typeslug",
            "wpc_format");

        foreach ($filters as $filter)
            if (has_filter($filter))
                $value = apply_filters($filter, $value, $this);

        $this->formatted_string_cache[$key] = $value;

        return $value;
    }

    /**
     * constructs a subclass of a specific type.
     */
    static function make_specific_class ($name, $typeslug="") {
        if (class_exists($name))
            return;

        if ($typeslug == "")
            $typeslug = strtolower($name);

        $classdef = "class $name extends ".get_called_class()." {
                protected \$typeslug = '$typeslug';
            }";

        eval($classdef);
    }
}
?>
