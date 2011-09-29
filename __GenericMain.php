<?php 


class __GenericMain {
	function __construct () {
//	SETUP HOOKS	
		add_action("init",					array($this, "init") );
	
		add_action('admin_print_scripts',	array($this, "custom_print_scripts") );
		add_action('admin_print_styles',	array($this, "custom_print_styles") );
	}

	function init () {
		global $wpc_relationships;
		global $wpc_content_types;

//	LOAD CONTENT TYPES 
		foreach (glob(__DIR__ . "/content_types/*.php") as $filename) {
			$class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);


			if ( !startsWith($class_name, "__") ) {
				require_once $filename;

				$instance_name	= lcfirst($class_name);
				$$instance_name	= new $instance_name();
			}
		}

//	LOAD RELATIONSHIPS 
		foreach (glob(__DIR__ . "/relationships/*.php") as $filename) {
			$class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);


			if ( !startsWith($class_name, "__") ) {
				require_once $filename;

				$instance_name	= lcfirst($class_name);
				$$instance_name	= new $instance_name();
			}
		}

//	CREATE AJAX-CALLBACKS
		if ( !empty($wpc_relationships) ) {
			__GenericRelationship::hookup_ajax_functions();
		}
	}

	function custom_print_scripts () {
		foreach (glob(__DIR__ . "/admin_scripts/*.js") as $filename) {
			$js_name = preg_replace("/\/?[^\/]+\/|\.js/", "", $filename);
			
			wp_enqueue_script($js_name, plugins_url("/admin_scripts/$js_name.js", __FILE__) );
		}

	}

	function custom_print_styles () {
		foreach (glob(__DIR__ . "/admin_styles/*.css") as $filename) {
			$css_name = preg_replace("/\/?[^\/]+\/|\.css/", "", $filename);
			
			wp_enqueue_style($css_name, plugins_url("/admin_styles/$css_name.css", __FILE__) );
		}
	}	
}

require_once "content_types/__GenericContentType.php";
require_once "relationships/__GenericRelationship.php";
require_once "metaboxes/__GenericMetabox.php";

 ?>