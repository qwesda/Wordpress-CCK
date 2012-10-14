<?php

/**
 *
 */

class TimeField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core () {
        $record = the_record();
        $value  = $record->__get($this->id);

        if ( count(explode(":", $value)) == 3 )
            $value  = join(":", explode(":", $value, -1) );
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
