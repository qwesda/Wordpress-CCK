<?php

/**
 *
 */

class IntField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";
     ?>
        <input type="text"
               id="wpc_field_<?php echo $this->id; ?>"
               class="wpc_input wpc_input_text <?php if ($this->localized) echo "wpc_localized_input";?>"
               name="<?php echo "wpc_$this->id"; ?>"
               value="<?php if ( !empty($value) ) echo htmlspecialchars($value, ENT_QUOTES); ?>"
               placeholder="<?php echo $this->hint; ?>" />
    <?php }
}

?>
