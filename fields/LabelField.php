<?php

/**
 *
 */

class LabelField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core () {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type ? $record->__get($this->id) : "";
    ?>
        <label class="wpc_input_label"><?php if ( !empty($value) ) echo $value; ?></label>
    <?php }

    function may_write () {
        return false;
    }
}

?>
