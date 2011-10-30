<?php

/*
Plugin Name: Wordpress-CCK
Description: Wordpress Plugin to manage content-types and relations
Author: Daniel Schwarz (qwesda@live.com), Tobias Florek (me@ibotty.net) and the ._______.
Version: 1.0
*/

require_once "helper.php";

require_once "GenericField.php";
require_once "GenericRecord.php";
require_once "GenericContentType.php";
require_once "GenericRelationship.php";
require_once "GenericMetabox.php";

global $wpc_version;
global $wpc_db_version;

$wpc_version    = "1.0";
$wpc_db_version = "1.0";

class WPCustom {
    function __construct () {
//  SETUP HOOKS
        add_action("init",                  array($this, "init") );

        add_action('admin_print_scripts',   array($this, "custom_print_scripts") );
        add_action('admin_print_styles',    array($this, "custom_print_styles") );


        // register hook to set current item in nav menus (default priority)
        add_filter('wp_get_nav_menu_items', array($this, "nav_menu_set_current"), 10, 3);
    }

    function init () {
        global $wpc_relationships;
        global $wpc_content_types;

        $this->custom_wp_print_scripts();
        $this->custom_wp_print_styles();

        $themes = get_themes();
        $theme  = get_current_theme();
        $theme_dir  = $themes[$theme]["Stylesheet Dir"];

//  LOAD FIELS
        foreach (glob(__DIR__ . "/fields/*.php") as $filename) {
            require_once $filename;
        }

//  LOAD CONTENT TYPES
        foreach (glob($theme_dir . "/content_types/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name();
        }

//  LOAD RELATIONSHIPS
        foreach (glob($theme_dir . "/relationships/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name();
        }

//  CREATE AJAX-CALLBACKS
        if ( !empty($wpc_relationships) ) {
            GenericRelationship::hookup_ajax_functions();
        }
    }

    function custom_print_scripts () {
        loadScriptsInPathWithIDPrefix   ("admin_libraries",    "core_admin_libraries");
        loadScriptsInPathWithIDPrefix   ("admin_scripts",      "core_admin_scripts");
    }

    function custom_print_styles () {
        loadStylesInPathWithIDPrefix    ("admin_styles",       "core_admin_styles");
    }

    function custom_wp_print_scripts () {
        wp_enqueue_script("jquery");

        loadScriptsInPathWithIDPrefix   ("frontend_libraries", "core_frontend_libraries");
        loadScriptsInPathWithIDPrefix   ("frontend_scripts",   "core_frontend_scripts");
    }

    function custom_wp_print_styles () {
        loadStylesInPathWithIDPrefix    ("frontend_styles",    "core_frontend_styles");
    }

    static function nav_menu_set_current($items, $menu, $args) {
        global $post;

        if (! $post)
            return $items;

        $post_type = get_post_type();

        if ($post_type == 'page')
            return $items;

        _log ('nav_menu_set_current');

        $ancestor_ids = array();
        $parent_ids = array();

        foreach ($items as &$nav_item)
            if (self::menu_is_current_nav_item ($nav_item->ID)) {
                $nav_item->classes[] = 'current-menu-item';
                $nav_item->classes[] = 'current-menu-item';
                $ancestor_id = $nav_item->ID;

                // set ancestor classes
                while( ($ancestor_id = get_post_meta($ancestor_id, '_menu_item_menu_item_parent', true)) && ! in_array($ancestor_id, $ancestor_ids))
                    $ancestor_ids[] = (int) $ancestor_id;
                $parent_ids[] = (int) $nav_item->menu_item_parent;
            }

        if (! empty($ancestor_ids))
            foreach ($items as &$nav_item)
                if (in_array($nav_item->ID, $ancestor_ids)) {
                    $nav_item->classes[] = 'current-menu-ancestor';
                    if (in_array($nav_item->ID, $parent_ids))
                        $nav_item->classes[] = 'current-menu-parent';
                }
        return $items;
    }
    static function menu_is_current_nav_item($nav_item_id) {
        global $post;

        $post_type = get_post_type();

        // the options to look for the nav_page for. first comes first.
        $nav_page_for_options = array (
            'wpc_nav_page_for_post_'.$post->ID,
            'wpc_nav_page_for_type_'.$post_type
        );

        foreach ($nav_page_for_options as $option) {
            $the_nav_id = get_option($option);
            if ($nav_item_id == $the_nav_id)
                return true;
        }
        return false;
    }
}

$WPCustom = new WPCustom();


function wpc_install() {
    global $wpdb;
    global $wpc_version;
    global $wpc_db_version;

    _ping();
    _log("wpc_install - version: $wpc_db_version");

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wpc_relations` (
                `relation_id` bigint(20) unsigned NOT NULL auto_increment,
                `post_from_id` bigint(20) unsigned NOT NULL,
                `post_to_id` bigint(20) unsigned NOT NULL,
                `relationship_id` varchar(255) NOT NULL default '',
                PRIMARY KEY  `relation_id` (`relation_id`),
                KEY `post_from_id`  (`post_from_id`),
                KEY `post_to_id` (`post_to_id`),
                KEY `relationship_id` (`relationship_id`)
                );";

    dbDelta($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wpc_relations_meta` (
                `meta_id` bigint(20) unsigned NOT NULL auto_increment,
                `relation_id` bigint(20) unsigned NOT NULL,
                `meta_key` varchar(255) default NULL,
                `meta_value` longtext,
                PRIMARY KEY  `meta_id` (`meta_id`),
                KEY `relation_id` (`relation_id`),
                KEY `meta_key` (`meta_key`)
            );
        );";

    dbDelta($sql);

    add_option("wpc_db_version",    $wpc_db_version);
}

register_activation_hook(__FILE__, 'wpc_install');

?>
