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
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";
     ?>
        <input type="text"
               id="wpc_field_<?php echo $this->id; ?>"
               class="wpc_input wpc_input_text <?php if ($this->localized) echo "wpc_localized_input";?>"
               name="<?php echo "wpc_$this->id"; ?>"
               value="<?php if ( !empty($value) ) echo htmlspecialchars($value, ENT_QUOTES); ?>"
               placeholder="<?php echo $this->hint; ?>" />
           <label class="wpc_helptext" for="wpc_field_<?php echo $this->id; ?>" style="display:none"><?php echo $this->helptext; ?></label>
    <?php }
}

?>
