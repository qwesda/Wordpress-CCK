<?php

/**
 *
 */

class TextAreaField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core () {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type ? $record->__get($this->id) : "";
    ?>
        <textarea rows="3" id="<?php echo "wpc_field_$this->id" ?>"
            class="wpc_input wpc_input_textarea <?php if ($this->localized) echo "wpc_localized_input"; ?>"
            placeholder="<?php echo str_replace("\\n", "\r", $this->hint); ?>"
            name="<?php echo "wpc_$this->id" ?>"><?php if ( !empty($value) ) echo esc_textarea($value); ?></textarea>
    <?php }
}

?>
