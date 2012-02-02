<?php

abstract class GenericField {
    public $id              = NULL;
    public $parent          = NULL;

    public $type            = "";
    public $label           = "";
    public $default         = "";
    public $hint            = "";

    public $required        = FALSE;

    /**
     * set to true to have qtranslate-like localization (e.g. [:en]english text[:de]german text)
     */
    public $localized = false;

    function __construct ($parent, $params) {
        $this->type = get_class($this);

        if ( is_array($params) ) $params = (object)$params;

        if ( !empty($params->id) )          $this->id           = $params->id;

        if ( !empty($params->label) )       $this->label        = $params->label;       else $this->label = ucwords( str_replace("_", " ", $this->id) );
        if ( !empty($params->default) )     $this->default      = $params->default;
        if ( !empty($params->hint) )        $this->hint         = $params->hint;
        if ( !empty($params->localized) && $params->localized === true ) $this->localized  = true;

        if ( !empty($this->id) && empty ($parent->fields[$this->id]) ) {
            $parent->fields[$this->id] = $this;
            $this->parent   = $parent;
        }

		if ( !empty($params->required) ) {
			$this->required = true;
		}
    }

    abstract function echo_field_core ($post_data = array());

    function echo_field_with_label_above ($post_data = array(), $label = "") { ?>
        <div class="wpc_form_field wpc_form_field_<?php echo $this->type ?>" id="wpc_form_field_id_<?php echo $this->id ?>">
            <label class="wpc_label_top" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->label ?></label>
            <?php $this->echo_field_core ($post_data); ?>
        </div><?php
    }

    function echo_field_with_label_left ($post_data = array(), $label = "") { ?>
        <div class="wpc_form_field wpc_form_field_<?php echo $this->type ?>" id="wpc_form_field_id_<?php echo $this->id ?>">
            <label class="wpc_label_left" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->label ?></label>
            <?php $this->echo_field_core ($post_data); ?>
        </div><?php
    }

    function echo_field($post_data) { ?>
        <div class="wpc_form_field wpc_form_field_<?php echo $this->type ?>" id="wpc_form_field_id_<?php echo $this->id ?>">
            <?php $this->echo_field_core ($post_data); ?>
        </div><?php
    }

}

?>
