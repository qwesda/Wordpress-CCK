<?php

/**
 *
 */

class DateField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    /**
     * the non-js-version is not localized at all. it would need a save-hook to convert the localized date back.
     * the js-version is partly localized. it needs a date_format in js.
     */


    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type && !$with_default_value ? $record->get($this->id) : "";

        ?>
        <input
            type="date"
            name="<?php echo "wpc_$this->id" ?>"
            id="<?php echo "wpc_field_$this->id" ?>"
            class="wpc_input wpc_input_date <?php if ($this->localized) echo "wpc_localized_input";?>"
            value="<?php echo $value; ?>"
            placeholder="2012-02-01" />
        <?php
    }
}

?>
