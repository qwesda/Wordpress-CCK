<?php

/**
 *
 */

class TextAreaField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core ($post_data = array ()) {  ?>
        <textarea rows="3" id="<?php echo "wpc_field_$this->id" ?>"
            class="wpc_input wpc_input_textarea <?php if ($this->localized) echo "wpc_localized_input"; ?>"
            placeholder="<?php echo str_replace("\\n", "\r", $this->hint); ?>"
            name="<?php echo "wpc_$this->id" ?>"><?php if ( !empty($post_data) ) echo esc_textarea($post_data[$this->id]); ?></textarea>
    <?php }
}

?>
