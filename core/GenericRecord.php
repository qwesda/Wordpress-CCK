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
    protected $typeslug = '';

    protected $post = array();
    protected $postrelations = array();
    protected $postmeta = array();

    function __construct($id=null) {
        $this->id = $id;

        // set typeslug to the lowercased classname, if not set
        if (empty($this->typeslug))
            $this->typeslug= strtolower(get_class($this));

        // get $post as hash for consistency reasons
        $this->post = get_post($this->id, 'ARRAY_A');
        $this->postmeta = get_post_custom($this->id);
    }

    function __get($attribute) {
        if (isset($this->postmeta[$attribute]))
            return $this->postmeta[$attribute];

        if (isset($this->post[$attribute]))
            return $this->post[$attribute];

        if (strpos($attribute, "connected_") === 0)
            return $this->get_connected(substr($attribute, strlen("connected_")));
        echo "attr after: $attribute\n";
        if (strpos($attribute, "formatted_") === 0)
            return $this->formatted_string(substr($attribute, strlen("formatted_")));

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
        if (isset($this->postmeta[$key]))
            $value = $this->postmeta[$key];
        else if (isset($this->post[$key]))
            $value = $this->post[$key];

        // check for the following filters, apply the first
        $filters = array("wpc_format_".$this->typeslug."_$key",
            "wpc_format_$this->typeslug",
            "wpc_format");
        foreach ($filters as $filter)
            if (has_filter($filter)) {
                $value = apply_filters($filter, $value, $this);
                break;
            }

        return $value;
    }
}

function wpc_make_generic_record_class($name, $typeslug="") {
    if (class_exists($name))
        return;

    if ($typeslug == "")
        $typeslug = strtolower($name);
    $classdef = "class $name extends GenericRecord {
            protected \$typeslug = '$typeslug';
        }";

    eval($classdef);
}
?>
