<?php 

/**
 * 
 */

class LabelField extends __GenericField {
	function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
	}


	function echo_field_core ($post_data = array ()) {	?>
		<label class="wpc_input_label"><?php if ( !empty($post_data) ) echo $post_data[$this->id] ?></label>
	<?php }
}

?>