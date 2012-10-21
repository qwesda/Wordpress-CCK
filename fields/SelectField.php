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

    function echo_field_core () {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type ? $record->__get($this->id) : "";
    ?>
        <select id="<?php echo "wpc_field_$this->id" ?>" class="wpc_input wpc_input_select"   name="<?php echo "wpc_$this->id" ?>" width="100%">
            <option value=""></option>
            <?php foreach ($this->options as $option): ?>
                <option value="<?php echo $option; ?>" <?php
                    if ( !empty($value) ) {
                        echo $value == $option ? 'selected' : '';
                    } else {
                        echo $this->default == $value ? 'selected' : '';
                    } ?>><?php echo $option ?></option>
            <?php endforeach ?>
        </select>
    <?php }
}

?>
