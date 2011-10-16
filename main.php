<?php

/*
Plugin Name: Wordpress-CCK
Description: Wordpress Plugin to manage connten-types and relations
Author: Daniel Schwarz and the ._______.
Version: 1.0
*/

require_once "core/__GenericMain.php";

global $wpc_version;
global $wpc_db_version;

$wpc_version    = "1.0";
$wpc_db_version = "1.0";

class WPCustom extends __GenericMain {
    function __construct() {
        parent::__construct();
    }

    function custom_print_scripts () {
        parent::custom_print_scripts();
    }

    function custom_print_styles () {
        parent::custom_print_styles();
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
