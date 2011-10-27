<?php 

/**
 * 
 */

class TextField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
    }


    function echo_field_core ($post_data = array ()) { 
     ?>
        <input type="text" class="wpc_input wpc_input_text" name="<?php echo "wpc_$this->id" ?>" id="<?php echo "wpc_$this->id" ?>" value="<?php if ( !empty($post_data) ) echo $post_data[$this->id]; ?>">
        <label class="wpc_hint" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->hint ?></label>
    <?php }
}

?>