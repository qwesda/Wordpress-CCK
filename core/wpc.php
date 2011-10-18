<?php

/*
Plugin Name: Wordpress-CCK
Description: Wordpress-Plugin to manage content-types and relations
Author: Daniel Schwarz, Tobias Florek
Version: 2011-10-17
*/

global $wpc;

class WPC {

    protected $version = '2011-10-17';
    protected $db_version = '1';

    protected $relations_table_name;
    protected $relationsmeta_table_name;


    /////////////////
    // public API
    ////////////////

    /**
     * registers a subclass of GenericType to be found in the file $filename.
     */
    function register_type($file) {

    }

    /**
     * registers a subclass of GenericRelationship to be found in the file $filename.
     */
    function register_relationship($file) {

    }

    /**
     * unregisters a subclass of GenericType to be found in the file $filename.
     */
    function unregister_type($file) {

    }

    /**
     * unregisters a subclass of GenericRelationship to be found in the file $filename.
     */
    function unregister_relationship($file) {

    }

    /**
     * register all types in the directory $dir. (see register_type.)
     */
    function register_types_in_dir($dir) {
        foreach (glob($dir.'/*.php') as $file)
            $this->register_type($file);
    }

    /**
     * register all relationships in the directory $dir. (see register_type.)
     */
    function register_relationships_in_dir($dir) {
        foreach (glob($dir.'/*.php') as $file)
            $this->register_relationship($file);
    }

    /**
     * deregister the types in the directory $dir.
     */
    function unregister_types_in_dir($dir) {
        foreach (glob($dir.'/*.php') as $file)
            $this->unregister_type($file);
    }
    /**
     * deregister all relationships in the directory $dir.
     */
    function unregister_relationships_in_dir($dir) {
        foreach (glob($dir.'/*.php') as $file)
            $this->unregister_relationship($file);
    }


    ////////////
    // Private
    // (in the way, that other plugins will not need anything from below)
    ////////////

    function __construct() {
        $this->relations_table_name = $wpdb->prefix.'wpc_relations';
        $this->relationsmeta_table_name = $wpdb->prefix.'wpc_relations_meta';


        // plugin hooks
        register_activation_hook(__FILE__, array($this, activate));
        register_deactivation_hook(__FILE__, array($this, deactivate));

        $old_version = get_option('wpc_version');
        $old_db_version = get_option('wpc_db_version');
        if ($old_version != $this->version) {
            $this->updated_from($old_version);
            if ($old_db_version != $this->db_version)
                $this->db_updated_from($old_db_version);
        }

        add_action("init",           array($this, "init") );
        add_action('plugins_loaded', array($this, "register_everything") );
    }

    function activate() {
        add_option('wpc_version', $this->version);
        add_option('wpc_db_version', $this->db_version);

        $this->create_tables();
    }

    function deactivate() {
    }

    function create_tables() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE IF NOT EXISTS `$this->relations_table_name` (
                `relation_id` bigint(20) unsigned NOT NULL auto_increment,
                `post_from_id` bigint(20) unsigned NOT NULL,
                `post_to_id` bigint(20) unsigned NOT NULL,
                `relationship_id` varchar(255) NOT NULL default '',
                PRIMARY KEY `relation_id` (`relation_id`),
                KEY `post_from_id`  (`post_from_id`),
                KEY `post_to_id` (`post_to_id`),
                KEY `relationship_id` (`relationship_id`)
                );";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `$this->relationsmeta_table_name` (
                `relation_id` bigint(20) unsigned NOT NULL auto_increment,
                `post_from_id` bigint(20) unsigned NOT NULL,
                `post_to_id` bigint(20) unsigned NOT NULL,
                `relationship_id` varchar(255) NOT NULL default '',
                PRIMARY KEY `relation_id` (`relation_id`),
                KEY `post_from_id`  (`post_from_id`),
                KEY `post_to_id` (`post_to_id`),
                KEY `relationship_id` (`relationship_id`)
                );";

        dbDelta($sql);
    }

    function updated_from ($old_version) {
        update_option('wpc_version', $this->version);
    }
    function db_updated_from($old_version) {
        update_option('wpc_db_version', $this->db_version);
        do_action ('wpc_reregister');
    }

    function init () {

    }

    function register_everything() {
        // let other plugins register new types
        do_action('wpc_register');
    }

    function load_thingies_from_file($file) {
        $classes = self::get_classes($file);
        include_once($file);
    }

    /**
     * returns all classes within a file, that are subclasses of the given class.
     */
    static function get_classes_with_superclass($file, $superclass) {
        $classes = array();

        $all_classes_before = get_declared_classes();

        if (in_array($file, get_included_files())) {
            include_once($file);
            $classes = array_diff(get_declared_classes(), $all_classes_before);
        } else {
            // slow path
            $code = file_get_contents($file);
            $tokens = token_get_all($file);

            $after_class_token = false;

            foreach ($tokens as $token) {
                if ($token[0] == T_CLASS) {
                    $after_class_token = true;
                } else if ($after_class_token && $token[0] == T_STRING) {
                    $classes[] = $token[0];
                    $after_class_token = false;
                }
            }
        }

        $classes = array_filter($classes, create_function('$c', "return is_subclass_of(\$c, '$superclass');"));

        return $classes;
    }
}

$wpc = new WPC();


// vim: ts=4 sw=4
?>
