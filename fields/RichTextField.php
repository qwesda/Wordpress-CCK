<?php

/**
 *
 */

class RichTextField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);

        $this->dont_auto_echo_metabox = !empty($params['dont_auto_echo_metabox']) ? true : false;
    }

    function echo_field_core () {
        $record = the_record();
        $value  = $record->__get($this->id);

        if (isset($value))
            $content = $value;
        else
            $content = "";

        wp_editor($content, "wpc_$this->id", array('editor_class'=>'wpc_input wpc_input_richtext'));
    }
}

?>