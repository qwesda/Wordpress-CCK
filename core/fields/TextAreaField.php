<?php 

/**
 * 
 */

class TextAreaField extends __GenericField {
	function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
	}


	function echo_field_core ($post_data) {	?>
		<textarea rows="3" id="<?php echo "wpc_$this->id" ?>"	name="<?php echo "wpc_$this->id" ?>"><?php echo esc_textarea($post_data[$this->id]); ?></textarea>
	<?php }
}

?>