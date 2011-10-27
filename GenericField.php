<?php 

abstract class GenericField {
    public $id              = NULL;
    public $parent          = NULL;

    public $type            = "";
    public $label           = "";
    public $default         = "";
    public $hint            = "";
    
    function __construct ($parent, $params) {
        $this->type = get_class($this);

        if ( is_array($params) ) $params = (object)$params; 

        if ( !empty($params->id) )          $this->id           = $params->id;

        if ( !empty($params->label) )       $this->label        = $params->label;       else $this->label = ucwords( str_replace("_", " ", $this->id) );
        if ( !empty($params->default) )     $this->default      = $params->default;
        if ( !empty($params->hint) )        $this->hint         = $params->hint;

        if ( !empty($this->id) && empty ($parent->fields[$this->id]) ) {
            $parent->fields[$this->id] = $this;
            $this->parent   = $parent;
        }

    }

    function echo_field_core ($post_data = array() ) {      
        echo "unhandeled field <b>$this->id</b> of type <i>$field->type</i>";
        _var_dump($field);
    }

    function echo_field_with_label_above ($post_data = array(), $label = "") { ?>
        <div class="wpc_form_field">
            <label class="wpc_label_top" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->label ?></label>
            <?php $this->echo_field_core ($post_data); ?>
        </div><?php
    }

    function echo_field_with_label_left ($post_data = array(), $label = "") { ?>
        <div class="wpc_form_field">
            <label class="wpc_label_left" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->label ?></label>
            <?php $this->echo_field_core ($post_data); ?>
        </div><?php
    }
    function echo_field($post_data) { ?>
        <div class="wpc_form_field">
            <?php $this->echo_field_core ($post_data); ?>
        </div><?php
    }
    
}

?>