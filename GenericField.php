<?php

abstract class GenericField {
    public $id              = NULL;
    public $parent          = NULL;

    public $type            = "";
    public $label           = "";
    public $default         = "";
    public $hint            = "";
    public $helptext        = "";

    public $edit_column     = false;
    public $sortable_column = false;

    public $required        = false;

    /**
     * set to true to have qtranslate-like localization (e.g. [:en]english text[:de]german text)
     */
    public $localized = false;

    public $printf_specifier = '%s';

    // set to 'r', 'w' or 'rw' for read only, write only and read-write access
    protected $access = 'rw';

    function __construct ($parent, $params) {
        $this->type = get_class_name($this);

        if ( is_array($params) ) $params = (object)$params;

        if ( !empty($params->id) )          $this->id           = $params->id;

        if ( !empty($params->label) )       $this->label        = $params->label;       else $this->label = ucwords( str_replace("_", " ", $this->id) );
        if ( !empty($params->default) )     $this->default      = $params->default;
        if ( !empty($params->hint) )        $this->hint         = $params->hint;
        if ( !empty($params->helptext) )    $this->helptext     = $params->helptext;
        if ( !empty($params->localized) && $params->localized === true ) $this->localized  = true;

        if ( !empty($this->id) && empty ($parent->fields[$this->id]) ) {
            $parent->fields[$this->id] = $this;
            $this->parent   = $parent;
        }

        if ( !empty($params->required) ) {
            $this->required = true;
        }

        if ( !empty($params->printf_specifier)) {
            $this->printf_specifier = $params->printf_specifier;
        }
    }

    abstract function echo_field_core ($with_default_value = false);

    function may_write($post_id = NULL) {
        return $this->access == 'rw' || $this->access == 'w';
    }

    function echo_field_with_label_above ($label = "", $with_default_value = false) {
        $label = !empty($label) ? $label : $this->label;
    ?>
        <div class="wpc_form_field wpc_form_field_<?php echo $this->type ?>" id="wpc_form_field_id_<?php echo $this->id ?>">
            <label class="wpc_label_top" for="<?php echo "wpc_$this->id" ?>"><?php echo $label ?></label>
            <?php $this->echo_field_core ($with_default_value); ?>
        </div><?php
    }

    function echo_field_with_label_left ($label = "", $with_default_value = false) {
        $label = !empty($label) ? $label : $this->label;
    ?>
        <div class="wpc_form_field wpc_form_field_<?php echo $this->type ?>" id="wpc_form_field_id_<?php echo $this->id ?>">
            <label class="wpc_label_left" for="<?php echo "wpc_$this->id" ?>"><?php echo $label ?></label>
            <?php $this->echo_field_core ($with_default_value); ?>
        </div><?php
    }

    function echo_field($with_default_value = false) { ?>
        <div class="wpc_form_field wpc_form_field_<?php echo $this->type ?>" id="wpc_form_field_id_<?php echo $this->id ?>">
            <?php $this->echo_field_core ($with_default_value); ?>
        </div><?php
    }

}

?>
