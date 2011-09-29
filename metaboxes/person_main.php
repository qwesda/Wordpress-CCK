<?php 
	
class person_main extends __GenericMetabox {
	function __construct () {
		$this->label = "Person";

		parent::__construct();
	}


	function echo_metabox ($post, $callback_args) {
		parent::echo_metabox(); 

		$post_data = $callback_args['args'];
	?>
			<style type="text/css">
				input#wpc_room,
				input#wpc_von_part,
				input#wpc_secretary_von_part,
				input#wpc_secretary_room,
				input#wpc_title,
				input#wpc_secretary_title {
					width: 60px;
				}
				input#wpc_website {
					width: 284px;
				}
			</style>

			<script type="text/javascript">
				function check_show_secretary() {
					if( !jQuery("#wpc_show_secretary").is(":checked") ){
						jQuery("#subform_secretary").addClass("disabled");
						jQuery("#subform_secretary :input").attr("disabled", true);
					} else {
						jQuery("#subform_secretary").removeClass("disabled");
						jQuery("#subform_secretary :input").removeAttr("disabled");
					}
				}

				jQuery("#wpc_show_secretary").live("change", check_show_secretary);
				jQuery(document).ready(check_show_secretary);
			</script>

			<div class="wpc_form_row_header">Name</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "title"); ?>
				<?php $this->echo_std_field($post_data, "givenname"); ?>
				<?php $this->echo_std_field($post_data, "von_part"); ?>
				<?php $this->echo_std_field($post_data, "surname"); ?>
			</div>

			<div class="wpc_form_row_header">Contact Information</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "email", "top"); ?>
				<?php $this->echo_std_field($post_data, "phone", "top"); ?>
				<?php $this->echo_std_field($post_data, "fax", "top"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "room", "top"); ?>
				<?php $this->echo_std_field($post_data, "website", "top"); ?>
			</div>

			<div class="clear"></div>

			<div class="wpc_form_vspacer"></div>

			<div class="wpc_subform">
				<div class="wpc_form_row">
					<?php $this->echo_std_field($post_data, "show_secretary"); ?>
				</div>

				<div id="subform_secretary">
					<div class="wpc_form_row_header">Name</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "secretary_title"); ?>
						<?php $this->echo_std_field($post_data, "secretary_givenname"); ?>
						<?php $this->echo_std_field($post_data, "secretary_von_part"); ?>
						<?php $this->echo_std_field($post_data, "secretary_surname"); ?>
					</div>

					<div class="wpc_form_row_header">Contact Information</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "secretary_email", "top"); ?>
						<?php $this->echo_std_field($post_data, "secretary_phone", "top"); ?>
						<?php $this->echo_std_field($post_data, "secretary_fax", "top"); ?>
					</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "secretary_room", "top"); ?>
					</div>
				</div>

				<div class="clear"></div>
			</div>

			<div class="clear"></div>


<?php 
	}

} 

?>