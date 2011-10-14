<?php 

/**
 * 
 */

class DateField extends __GenericField {
	function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
	}


	function echo_field_core ($post_data = array ()) {	?>
		<input type="text" name="<?php echo "wpc_$this->id" ?>" class="wpc_input wpc_input_date" id="<?php echo "wpc_$this->id" ?>" value="<?php if ( !empty($post_data) ) echo $post_data[$this->id]; ?>">
		<label class="wpc_hint" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->hint ?></label>
	<?php }
}

?>