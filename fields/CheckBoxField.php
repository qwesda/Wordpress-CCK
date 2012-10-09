<?php

/**
 *
 */

class CheckBoxField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core () {
        $record = the_record();
        $value = $record->get($this->id);
        ?>
        <input type="checkbox" class="wpc_input wpc_input_checkbox" name="<?php echo "wpc_$this->id" ?>" id="<?php echo "wpc_field_$this->id" ?>" value="true" <?php echo $value ? "checked=\"checked\"" : ""; ?> /> <label for="<?php echo "wpc_field_$this->id" ?>"><?php echo $this->label ?></label>
    <?php }
}

?>
