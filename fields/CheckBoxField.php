<?php 

/**
 * 
 */

class CheckBoxField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
    }


    function echo_field_core ($post_data = array ()) {  ?>
        <input type="checkbox" class="wpc_input wpc_input_checkbox" name="<?php echo "wpc_$this->id" ?>" id="<?php echo "wpc_$this->id" ?>" value="true" <?php if ( !empty($post_data) ) echo $post_data[$this->id] == "true" ? "checked=\"checked\"" : ""; ?> /><?php echo $this->label ?>
    <?php }
}

?>