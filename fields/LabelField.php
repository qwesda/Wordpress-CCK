<?php

/**
 *
 */

class LabelField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";
    ?>
        <label class="wpc_input_label"><?php if ( !empty($value) ) echo $value; ?></label>
    <?php }

    function may_write ($post_id = NULL) {
        return false;
    }
}

?>
