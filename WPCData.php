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
    protected $id;

    /**
     * a short name for the type of the data
     */
    protected $typeslug;

    /*
     * the data as associative array
     */
    protected $data;

    protected $data_keys = array();

    protected $data_to_update = array();

    /*
     * the meta data as associative array
     */
    protected $meta;

    protected $meta_keys = array();

    protected $meta_to_update = array();

    /*
     * an associative array. keys are the connected types.
     */
    protected $connected_cache = array();

    protected $formatted_string_cache = array();

    protected $write_ro = null;

    /**
     * constructor for a Record.
     *
     * If $data and $meta are set, use them (for when they are already fetched one way or another).
     * Both must be an associative array.
     */
    protected function __construct($data=null, $meta=null) {
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
        return $this->get($attribute);
    }

    function get($attribute) {
        if ($attribute === "id")
            return $this->id;

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
            return $this->get_connected(substr($attribute, strlen("connected_")), false);

        if (strpos($attribute, "reverse_connected_") === 0) {
            return $this->get_connected(substr($attribute, strlen("reverse_connected_")), true);
        }

        $formatted_string = $this->formatted_string($attribute);
        if ( !empty( $formatted_string ) )
            return $formatted_string;

        // ButterLog::debug(get_class($this)." does not have attribute '$attribute'.");
        // return empty string for non-existing attributes.
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
            && $this->exist_connected(substr($attribute, strlen("connected_")), false))
            return true;

        if (strpos($attribute, "reverse_connected_") === 0
            && $this->exist_connected(substr($attribute, strlen("reverse_connected_")), true))
            return true;

        if (strpos($attribute, "all_") === 0)
            return isset($this->meta["$attribute"]);

        if (strpos($attribute, "formatted_") === 0)
            return (isset($this->data[$attribute]) || isset($this->meta["$attribute"]));

        return false;
    }

    function __set($key, $val) {
        $this->set($key, $val);
    }

    function set($key, $val) {
        if (in_array($key, $this->data_keys)) {
            $this->data_to_update[$key] = $val;

            // update internal state before commit
            $this->data[$key] = $val;
        } else if (in_array($key, $this->meta_keys)) {
            $this->meta_to_update[$key] = $val;

            // update internal state before commit
            $this->meta[$key] = $val;
        } else
            ButterLog::warn("Cannot update \"$key\": Not a registered key.");

        return $this;
    }

    abstract function delete();
    abstract function commit($write_ro=false);
    abstract protected function load_data();
    abstract protected function load_meta();
    abstract protected function get_field_type($field_key);

    protected function get_connected($other_type, $reverse) {
        $cahce_id = ($reverse ? "reverse_" : "") . "$other_type";

        if (! isset($this->connected_cache[$cahce_id])) {
            $connection = $this->connected_by_id($other_type, $reverse);

            if ($connection)
                $this->connected_cache[$cahce_id] = $connection;

            if (empty($connection)) {
                ButterLog::warn("No connected ".$other_type."s found for $this->typeslug.");
                return;
            }
        }

        return $this->connected_cache[$cahce_id];
    }
    /**
     * checks, whether there exist connected thingies
     *
     * always returns false. Overwrite for connected thingies.
     */
    protected function exist_connected($other_type, $reverse) {
        return false;
    }
    /**
     * returns connected thingies of type $other_type.
     *
     * always returns null, Overwrite for connected types.
     */
    protected function connected_for_type($other_type, $reverse) {
        return null;
    }

    protected function connected_by_id($db_typeslug, $reverse) {
        return null;
    }

    function formatted_string ($key, $uncached = false) {
        // shortcut if it is already cached
        if ( !$uncached && isset($this->formatted_string_cache[$key]))
            return $this->formatted_string_cache[$key];

        // default to empty string
        $value = '';

        // apply the following filters in order
        $filters = array("wpc_format_".$this->typeslug."_$key",
            "wpc_format_$this->typeslug",
            "wpc_format");

        foreach ($filters as $filter) {
            if (has_filter($filter)) {
                $value = apply_filters($filter, $value, $this);
            }
        }

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


  protected function sub_dump($key, $val) {
    $type = gettype($key);

    switch (gettype($val)) {
      case 'string':
?><tr><td><span class="var_name"><?php echo $key ?></span> <span class="var_type"><?php echo $type ?></span></td><td><?php echo substr($val, 0, 100); if (strlen($val) > 100) echo "<span class='var_ellipsis'>...</span>" ?></td></tr><?php
        break;
      default:
?><tr><td><span class="var_name"><?php echo $key ?></span> <span class="var_type"><?php echo $type ?></span></td><td><?php $str_val = (string)$val; echo substr($str_val, 0, 100); if (strlen($str_val) > 100) echo "<span class='var_ellipsis'>...</span>" ?></td></tr><?php
        break;
    }
  }

    public function dump() {
        if (empty($this->data))
            $this->load_data();

        if (empty($this->meta))
            $this->load_meta();

       ?>

       <a class="var_dump_toggle" href="#" onclick="jQuery(this).next().toggle(); return false;"><span class="var_typeslug"><?php echo ucfirst($this->typeslug) ?> <span class="var_count">(<?php echo count($this->data)+count($this->meta) ?>)</span></a>
    <div class="var_dump" style="display: none;">
      <table border="0" cellspacing="5" cellpadding="5" class="var_dump">
      <tr class="var_dump_heading"><td>Data <span class="var_count">(<?php echo count($this->data) ?>)</td><td></td></tr>
      <?php foreach ($this->data as $key => $value) {
        $this->sub_dump($key, $value);
      } ?>

      <tr class="var_dump_heading"><td>Meta <span class="var_count">(<?php echo count($this->meta) ?>)</td><td></td></tr>

      <?php foreach ($this->meta as $key => $value) {
        $this->sub_dump($key, $value);
      } ?>
      </table>

       <div><?php
    }


    function write_ro($write_ro) {
        $this->write_ro = $write_ro;
        return $this;
    }
}
?>
