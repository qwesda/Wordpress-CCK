<?php

/**
 *
 */

class SelectField extends GenericField {
    public $options = array();

    function __construct ($parent, $params) {
        if ( !empty($params['options']) )   $this->options = $params['options'];

        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";

        if ( empty($value) && !$with_default_value) {
            $value = $this->default;
        }
    ?>
        <select id='<?php echo "wpc_field_$this->id" ?>' class="wpc_input wpc_input_select"   name='<?php echo "wpc_$this->id" ?>' width="100%">
            <option value='' <?php if ( empty($value) ) echo 'selected'; ?>></option>
            <?php foreach ($this->options as $name => $option): ?>
                <option value="<?php echo $option; ?>" <?php
                    if ( $value == $option ) echo 'selected'; ?>><?php echo is_int($name) ? $option : $name ?></option>
            <?php endforeach ?>
        </select>
        <label class="wpc_helptext" for="wpc_field_<?php echo $this->id; ?>" style="display:none"><?php echo $this->helptext; ?></label>
    <?php }
}

?>
