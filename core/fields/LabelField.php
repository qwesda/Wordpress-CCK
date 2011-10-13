<?php 

/**
 * 
 */

class LabelField extends __GenericField {
	function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
	}


	function echo_field_core ($post_data) {	?>
		<label class="wpc_input_label"><?php echo $post_data[$this->id] ?></label>
	<?php }
}

?>