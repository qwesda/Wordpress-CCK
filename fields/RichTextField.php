<?php

/**
 *
 */

class RichTextField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core ($post_data = array ()) {
        if (isset($post_data[$this->id]))
            $content = $post_data[$this->id];
        else
            $content = "";

        wp_editor($content, "wpc_$this->id", array('editor_class'=>'wpc_input wpc_input_richtext'));
    }
}

?>
