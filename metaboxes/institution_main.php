<?php 
	
class institution_main extends __GenericMetabox {
	function __construct () {
		$this->label = "Institution";

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
				input#wpc_secretary_room {
					width: 60px;
				}
				input#wpc_title,
				input#wpc_secretary_title {
					width: 60px;
				}
				input#wpc_website {
					width: 284px;
				}
				#wpc_department {
					width : 346px;
				}
			</style>

			<script type="text/javascript">
				function check_show_different_postal_address() {
					if( !jQuery("#wpc_different_postal_address").is(":checked") ){
						jQuery("#subform_different_postal_address").addClass("disabled");
						jQuery("#subform_different_postal_address :input").attr("disabled", true);
					} else {
						jQuery("#subform_different_postal_address").removeClass("disabled");
						jQuery("#subform_different_postal_address :input").removeAttr("disabled");
					}
				}

				jQuery("#wpc_different_postal_address").live("change", check_show_different_postal_address);
				jQuery(document).ready(check_show_different_postal_address);
			</script>

			<div class="wpc_form_row_header">Name</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "name"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "short_name", "top"); ?>
				<?php $this->echo_std_field($post_data, "alias", "top"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "type", "top"); ?>
			</div>

			<div class="wpc_form_row_header">Contact Information</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "department", "top"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "phone", "top"); ?>
				<?php $this->echo_std_field($post_data, "fax", "top"); ?>
			</div>

			<div class="clear"></div>

			<div class="wpc_form_vspacer"></div>

			<div class="wpc_subform">
				<div class="wpc_form_row">
					<?php $this->echo_std_field($post_data, "different_postal_address"); ?>
				</div>

				<div id="subform_different_postal_address">
					
					<div class="wpc_form_row_header">Location Address</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "postal_street", "top"); ?>
						<?php $this->echo_std_field($post_data, "postal_number", "top"); ?>
					</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "postal_city", "top"); ?>
						<?php $this->echo_std_field($post_data, "postal_postal_code", "top"); ?>
					</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "postal_district", "top"); ?>
					</div>
					<div class="wpc_form_row">
						<?php $this->echo_std_field($post_data, "postal_country", "top"); ?>
					</div>

				</div>

				<div class="clear"></div>
			</div>


			<div class="clear"></div>


<?php 
	}

} 

?>