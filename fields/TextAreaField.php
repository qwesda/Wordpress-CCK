<?php

/**
 *
 */

class TextAreaField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->get_plain($this->id) : "";
    ?>
        <textarea rows="3" id="<?php echo "wpc_field_$this->id" ?>"
            class="wpc_input wpc_input_textarea <?php if ($this->localized) echo "wpc_localized_input"; ?>"
            placeholder="<?php echo str_replace("\\n", "\r", $this->hint); ?>"
            name="<?php echo "wpc_$this->id" ?>"><?php if ( !empty($value) ) echo esc_textarea($value); ?></textarea>
           <label class="wpc_helptext" for="wpc_field_<?php echo $this->id; ?>" style="display:none"><?php echo $this->helptext; ?></label>
    <?php }
}

?>
