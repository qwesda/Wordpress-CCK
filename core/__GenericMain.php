<?php

require_once "helper.php";

require_once "__GenericField.php";
require_once "__GenericContentType.php";
require_once "__GenericRelationship.php";
require_once "__GenericMetabox.php";

class __GenericMain {
    function __construct () {
//  SETUP HOOKS
        add_action("init",                  array($this, "init") );

        add_action('admin_print_scripts',   array($this, "custom_print_scripts") );
        add_action('admin_print_styles',    array($this, "custom_print_styles") );
    }

    function init () {
        global $wpc_relationships;
        global $wpc_content_types;

        $this->custom_wp_print_scripts();
        $this->custom_wp_print_styles();

//  LOAD FIELS
        foreach (glob(__DIR__ . "/fields/*.php") as $filename) {
            require_once $filename;
        }

//  LOAD CONTENT TYPES
        foreach (glob(__DIR__ . "/../custom/content_types/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name();
        }

//  LOAD RELATIONSHIPS
        foreach (glob(__DIR__ . "/../custom/relationships/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name();
        }

//  CREATE AJAX-CALLBACKS
        if ( !empty($wpc_relationships) ) {
            __GenericRelationship::hookup_ajax_functions();
        }
    }

    function custom_print_scripts () {
        loadScriptsInPathWithIDPrefix   ("core/admin_libraries",    "core_admin_libraries");
        loadScriptsInPathWithIDPrefix   ("core/admin_scripts",      "core_admin_scripts");
    }

    function custom_print_styles () {
        loadStylesInPathWithIDPrefix    ("core/admin_styles",       "core_admin_styles");
    }

    function custom_wp_print_scripts () {
        wp_enqueue_script("jquery");

        loadScriptsInPathWithIDPrefix   ("core/frontend_libraries", "core_frontend_libraries");
        loadScriptsInPathWithIDPrefix   ("core/frontend_scripts",   "core_frontend_scripts");
    }

    function custom_wp_print_styles () {
        loadStylesInPathWithIDPrefix    ("core/frontend_styles",    "core_frontend_styles");
    }   
}

?>
