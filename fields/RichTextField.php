<?php

/**
 *
 */

class RichTextField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);

        $this->dont_auto_echo_metabox = !empty($params['dont_auto_echo_metabox']) ? true : false;
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type && !$with_default_value ? $record->get_plain($this->id) : "";

        if (isset($value))
            $content = $value;
        else
            $content = "";

        wp_editor($content, "wpc_$this->id", array('editor_class'=>'wpc_input wpc_input_richtext'));
    }
}

?>