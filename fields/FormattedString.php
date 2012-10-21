<?php

/**
 *
 */

class FormattedString extends GenericField {
    function __construct ($parent, $params, $callback) {
        parent::__construct ($parent, $params);

        $filter_id = "wpc_format_".$parent->id."_".$this->id;

        add_filter($filter_id, $callback, 10, 2);
    }

    function may_write () {
        return false;
    }

    function echo_field_core () {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type ? $record->__get($this->id) : "";
    ?>
        <label class="wpc_input_label"><?php if ( !empty($value) ) echo $value; ?></label>
    <?php }
}

?>
