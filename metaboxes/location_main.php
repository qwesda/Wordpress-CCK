<?php 
	
class location_main extends __GenericMetabox {
	function __construct () {
		$this->label = "Location";

		parent::__construct();
	}


	function echo_metabox ($post, $callback_args) {
		parent::echo_metabox(); 

		$post_data = $callback_args['args'];
	?>
			<style type="text/css">
				input#wpc_alias,
				input#wpc_number,
				input#wpc_postal_code {
					width: 80px;
				}
				

			</style>

			<div class="wpc_form_row_header">Name</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "name"); ?>
				<?php $this->echo_std_field($post_data, "alias"); ?>
			</div>

			<div class="wpc_form_row_header">Location Address</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "street", "top"); ?>
				<?php $this->echo_std_field($post_data, "number", "top"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "city", "top"); ?>
				<?php $this->echo_std_field($post_data, "postal_code", "top"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "state", "top"); ?>
			</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "country", "top"); ?>
			</div>

			<div class="clear"></div>

			<div class="wpc_form_row_header">Geodata</div>
			<div class="wpc_form_row">
				<?php $this->echo_std_field($post_data, "latitude", "top"); ?>
				<?php $this->echo_std_field($post_data, "longitude", "top"); ?>
			</div>
			<div class="clear"></div>

			<div class="wpc_form_row">
				<div id="map_canvas_data"></div>
				<div id="map_canvas"></div>
		
				<div id="div-place-search-for-location" class="float-left">
					<input class="wpc_input_text" id="search-for-location"	type="text"	value="" />
					<label class="wpc_hint" for="search-for-location">search for location</label>	
					
					<ul id="search-for-location-results"></ul>
				</div>
			</div>


			<div class="clear"></div>

<?php 
	}

} 

?>