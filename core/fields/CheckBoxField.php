<?php 

/**
 * 
 */

class CheckBoxField extends __GenericField {
	function __construct ($parent, $params) {
        parent::__construct ($parent, $params); 
	}


	function echo_field_core ($post_data) {	?>
		<input type="checkbox" name="<?php echo "wpc_$this->id" ?>" id="<?php echo "wpc_$this->id" ?>" value="true" <?php echo $post_data[$this->id] == "true" ? "checked=\"checked\"" : ""; ?> /><?php echo $this->label ?>
	<?php }
}

?>