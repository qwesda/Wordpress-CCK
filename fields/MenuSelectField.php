<?php

/**
 *
 */

class MenuSelectField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";

        $menues = get_terms('nav_menu');
     ?>

         <select id='<?php echo "wpc_field_$this->id" ?>' class="wpc_input wpc_input_select"   name='<?php echo "wpc_$this->id" ?>' width="100%">
             <option value='' <?php if ( empty($value) ) echo 'selected'; ?>></option>
             <?php  foreach ($menues as $menue): ?>
                 <option value="<?php echo $menue->term_id; ?>" <?php
                     if ( $value == $menue->term_id ) echo 'selected'; ?>><?php echo $menue->name ?></option>
             <?php endforeach  ?>
         </select>

           <label class="wpc_helptext" for="wpc_field_<?php echo $this->id; ?>" style="display:none"><?php echo $this->helptext; ?></label>
    <?php }
}

?>
