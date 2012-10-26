<?php

/**
 *
 */

class MonthField extends GenericField {
    public $options = array(
         "1" => "Jan",
         "2" => "Feb",
         "3" => "Mar",
         "4" => "Apr",
         "5" => "May",
         "6" => "Jun",
         "7" => "Jul",
         "8" => "Aug",
         "9" => "Sep",
        "10" => "Okt",
        "11" => "Nov",
        "12" => "Dec"
    );

    function __construct ($parent, $params) {
        if ( !empty($params['options']) )   $this->options = $params['options'];

        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";
    ?>
        <select id="<?php echo "wpc_field_$this->id" ?>" class="wpc_input wpc_input_select"   name="<?php echo "wpc_$this->id" ?>" width="100%">
            <option value=""></option>
            <?php foreach ($this->options as $option => $label): ?>
                <option value="<?php echo $option; ?>" <?php
                    if ( !empty($value) ) {
                        echo $value == $option ? 'selected' : '';
                    } else {
                        echo $this->default == $value ? 'selected' : '';
                    } ?>><?php echo $label ?></option>
            <?php endforeach ?>
        </select>
    <?php }
}

?>
