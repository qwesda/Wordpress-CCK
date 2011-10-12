<?php 

class __GenericMetabox {
	public $metabox_id		= NULL;
	public $content_type	= NULL;
	public $label			= "";

	public $context			= "advanced";
	public $priority		= "high";
	
	function __construct () {

//	SET DEFAULTS
        if(empty($this->metabox_id))			$this->metabox_id    	= strtolower(get_class($this));
        if(empty($this->label))					$this->label    		= strtolower(str_replace("_", " ", $this->metabox_id));

	}

	function register_metabox() {
		
	}

	function echo_metabox () {
		$this->content_type->first_metabox();
	}

	function echo_std_field($post_data, $field_key, $label = "") {
		$field = $this->content_type->fields[$field_key];

		if (!empty($field)) {?>
			<div class="wpc_form_field"><?php

			switch ($field->type) {
				case "text" :
				case "textarea" :
				case "select" : ?>
					<?php if ($label == "top"): ?>
						<label class="wpc_label_top" for="<?php echo "wpc_$field_key" ?>"><?php echo $field->label ?></label>
					<?php endif ?>
				
			<?php	break;
			}

			switch ($field->type) {
				case "text" : ?>
					<input type="text" class="wpc_input_text" name="<?php echo "wpc_$field_key" ?>" id="<?php echo "wpc_$field_key" ?>" value="<?php echo $post_data[$field_key]; ?>">
					<label class="wpc_hint" for="<?php echo "wpc_$field_key" ?>"><?php echo $field->hint ?></label>
				<?php $field->handeled = true;
					break;
				
				case "checkbox" : ?>
					<input type="checkbox" name="<?php echo "wpc_$field_key" ?>" id="<?php echo "wpc_$field_key" ?>" value="true" <?php echo $post_data[$field_key] == "true" ? "checked=\"checked\"" : ""; ?> /><?php echo $field->label ?>
				<?php $field->handeled = true;
					break;
				
				case "textarea" : ?>
					<textarea rows="3" id="<?php echo "wpc_$field_key" ?>"	name="<?php echo "wpc_$field_key" ?>"><?php echo esc_textarea($post_data[$field_key]); ?></textarea>
				<?php $field->handeled = true;
					break;
				
				case "select" : ?>
					<select id="<?php echo "wpc_$field_key" ?>"	name="<?php echo "wpc_$field_key" ?>" width="100%">
						<option value=""></option>
						<?php foreach ($field->options as $value): ?>
							<option value="<?php echo $value ?>" <?php echo $post_data[$field_key] == $value ? 'selected' : '' ?>><?php echo $value ?></option>
						<?php endforeach ?>
					</select> 
					<?php $field->handeled = true;
					break; 
				
				default:
					echo "unhandeled field <b>$field_key</b> of type <i>$field->type</i>";
					_var_dump($field);
					break;
			}
			?>
			</div><?php

		} 
	}
	
}

?>