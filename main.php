<?php

/*
Plugin Name: Wordpress-CCK
Description: Wordpress Plugin to manage content-types and relations
Author: Daniel Schwarz (dan@bytesandbutter.de), Tobias Florek (tob@bytesandbutter.de) and the ._______.
Version: 1.0
*/

require_once "vendor/butterlog/ButterLog.php";
require_once "helper.php";

global $wpc_version;
global $wpc_db_version;

$wpc_version    = "1.1";
$wpc_db_version = "1.1";

class WPCustom {
    protected $post_is_updated = false;

    function __construct () {
        // setup autoloading of class files
        spl_autoload_register(array(__CLASS__, 'autoload'));

//  SETUP HOOKS
        register_activation_hook(__CLASS__, array($this, 'wpc_install'));

        add_action("init",                  array($this, "init") );
        add_action('plugins_loaded',        array($this, "plugins_loaded"));

        add_action('widgets_init',          array($this, "widgets_init") );

        add_action('save_post',             array($this, 'save_post'), 10, 2);
        add_action('pre_post_update',       array($this, 'pre_post_update'));
        add_action('delete_post',           array($this, 'delete_post'));
        add_action('add_meta_boxes',        array($this, "add_meta_boxes") );

        // register hook to set current item in nav menus (default priority)
        add_filter('wp_get_nav_menu_items', array($this, "nav_menu_set_current"), 10, 3);

        add_filter('the_content',           array($this, 'the_content') );
        add_action('wp_insert_post_data',   array($this, 'wp_insert_post_data'), 10, 2);

        add_filter('display_post_states',   array($this, 'display_post_states') );

        new Settings();
    }

    function init () {
        global $wpc_relationships;
        global $wpc_content_types;

        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        //add_action('admin_enqueue_scripts',  array($this, "admin_enqueue_scripts") );
        //add_action('admin_enqueue_styles',  array($this, "admin_enqueue_styles") );

        if ( !is_admin() ) {
            $this->wp_enqueue_scripts();
            $this->wp_enqueue_styles();
        } else {
            //$this->admin_enqueue_scripts();
            $this->admin_enqueue_styles();

            add_action('admin_enqueue_scripts',  array($this, "admin_enqueue_scripts") );
            //add_action('admin_enqueue_styles',  array($this, "admin_enqueue_styles") );
        }

        register_post_status( 'without_public_page', array(
            'label' => "Without Public Page",
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Without Public Page <span class="count">(%s)</span>', 'Without Public Page <span class="count">(%s)</span>' ),
        ) );

//  LOAD HELPERS
        foreach (glob($theme_dir . "/helpers/*.php") as $filename) {
            require_once $filename;
        }

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

            $wpc_content_types[$instance_name] = $$instance_name;
        }

//  LOAD TAXONOMIES
        foreach (glob($theme_dir . "/taxonomies/*.php") as $filename) {
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

//  LOAD INDICES
        foreach (glob($theme_dir . "/indices/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name();
        }

//  LOAD PLUGIN BACKEND PAGES
        foreach (glob(__DIR__ . "/backend_pages/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name( );
        }

//  LOAD THEME BACKEND PAGES
        foreach (glob($theme_dir . "/backend_pages/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            $instance_name  = lcfirst($class_name);
            $$instance_name = new $instance_name( );
        }

//  CREATE AJAX-CALLBACKS
        if ( !empty($wpc_relationships) ) {
            GenericRelationship::hookup_ajax_functions();
        }
    }

    function display_post_states( $states ) {
        global $post;

        if ( get_post_status($post->ID) == "without_public_page" ) {
            $states[] = "without public page";
        }

        return $states;
    }

    function save_post ($post_id, $post) {
        global $_POST;
        global $wpc_content_types;

        if (! isset($wpc_content_types[$post->post_type]))
            return;

        $type = $wpc_content_types[$post->post_type];

        $postmeta = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'wpc_') === 0)
                $postmeta[substr($key, 4)] = $value;
            if ($key == "post_status")
                $postmeta[$key] = $value;

        }

        if ($this->post_is_updated) {
            $this->post_is_updated = false;
            return $type->update_post($post_id, $post, $postmeta);
        }
        else
            return $type->new_post($post_id, $post, $postmeta);
    }

    function pre_post_update($post_id) {
        $this->post_is_updated  = true;
    }

    function delete_post ($post_id) {
        global $wpc_content_types;

        $post = get_post($post_id);

        if (! isset($wpc_content_types[$post->post_type]))
            return;

        $type = $wpc_content_types[$post->post_type];
        return $type->delete_post($post_id, $post);
    }

    function add_meta_boxes ($post_type) {
        global $wpc_relationships;
        global $wpc_content_types;

        $post_id = get_the_ID();

        if (empty($wpc_content_types[$post_type]))
            return ;

        $post      = get_post($post_id);

        $post_type = $wpc_content_types[$post_type];

        $theme     = wp_get_theme();
        $theme_dir = $theme["Stylesheet Dir"];

//  ADD METABOXES
        foreach ($post_type->fields as $field) {
            if ($field->type == "RichTextField" && empty($field->dont_auto_echo_metabox) ) {
                add_meta_box(
                    $post_type->id."_".$field->id,
                    $field->label,
                    array(&$this, "echo_richtext_metabox"),
                    $post_type->id,
                    "advanced",
                    "high",
                    array('field' => $field)
                );
            }
        }

        foreach (glob("$theme_dir/metaboxes/" . $post_type->id . "_*.php") as $filename) {
            $metabox_class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);
            $metabox_class_id   = $metabox_class_name;

            if ( !startsWith($metabox_class_name, "__") ) {
                require_once $filename;

                $instance_name  = lcfirst($metabox_class_name);
                $$instance_name = new $metabox_class_name();

                $$instance_name->content_type = $post_type;

                add_meta_box(
                    $$instance_name->metabox_id,
                    $$instance_name->label,
                    array(&$$instance_name, "echo_metabox"),
                    $post_type->id,
                    $$instance_name->context,
                    $$instance_name->priority,
                    array()
                );
            }
        }
        /*
        if ($wpc_relationships)
        foreach ($wpc_relationships as $wpc_relationship_key => $wpc_relationship) {
            if ($post_type->id == $wpc_relationship->post_type_from_id || $post_type->id == $wpc_relationship->post_type_to_id) {
                add_meta_box(
                    "$post_type->id-relationship",
                    "Relationships",
                    array("GenericRelationship", "echo_relations_metabox" ),
                    $post_type->id
                );

                break;
            }
        }
        */
    }

    function the_content ($input_content) {
        global $post, $content;
        global $wpc_content_types;

        $content = $input_content;

        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        if (!empty($post) && isset($wpc_content_types[$post->post_type])) {
            $filename = "$theme_dir/content_overrides/{$post->post_type}.php";
            if (file_exists($filename))
                $content = _compile($filename);
        }

        return $content;
    }

    function wp_insert_post_data($data, $postarr) {
        global $wpc_content_types;

        $type = $data['post_type'];

        if ( !isset($wpc_content_types[$type]) )
            return $data;

        $wpctype = $wpc_content_types[$type];

        return $wpctype->wp_insert_post_data($data, $postarr);
    }

    function echo_richtext_metabox ($post, $metabox) {
        $field  = $metabox['args']['field'];

        $field->echo_field();

        echo '<div class="clear"></div>';
    }

    function wpc_install() {
        $this->db_install();
        add_option("wpc_db_version", $wpc_db_version);
    }

    function db_install() {
        global $wpdb;
        global $wpc_db_version;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        /*
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
        */
    }

    function plugins_loaded() {
        global $wpdb;
        global $wpc_db_version;
        /*
        $old_db_version = get_option("wpc_db_version");
        if ($old_db_version != $wpc_db_version) {
            $this->db_install();

            // the plugin had a bug until db_version 1.0 where all relation's
            // meta keys had "field_" appended
            if ($old_db_version == 1.0) {
                $query = "UPDATE {$wpdb->prefix}wpc_relations_meta
                    SET meta_key=SUBSTR(meta_key,7)
                    WHERE LEFT(meta_key,6)='field_';";
                $wpdb->query($query);
            }
            update_option("wpc_db_version", $wpc_db_version);
        }
        */
    }

    function widgets_init () {
        $theme      = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

//  LOAD WIDGETS
        foreach (glob($theme_dir . "/widgets/*.php") as $filename) {
            $class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);

            require_once $filename;

            register_widget($class_name);
        }
    }

    function admin_enqueue_scripts () {
        // add wpc-object for javascript
        // TODO: this should check for qtranslate and set it to array() if not loaded.
        $languages = array("de", "en");
        foreach ($languages as &$lang)
            $lang = "'$lang'";
        ?>
        <script type='text/javascript'>
            wpc = {
                enabled_languages: [<?php echo join(", ", $languages);?>],
                default_language:  <?php echo $languages[0];?>
            };

        </script>
        <?php
        loadScriptsInPathWithIDPrefix   (WP_PLUGIN_DIR . "/Wordpress-CCK/admin_libraries",    "core_admin_libraries");
        loadScriptsInPathWithIDPrefix   (WP_PLUGIN_DIR . "/Wordpress-CCK/admin_scripts",      "core_admin_scripts");

        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        loadScriptsInPathWithIDPrefix   ($theme_dir . "/admin_scripts",   "theme_backend_scripts");

    }

    function admin_enqueue_styles () {
        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        loadStylesInPathWithIDPrefix    (WP_PLUGIN_DIR . "/Wordpress-CCK/admin_styles",    "core_frontend_styles");
        loadStylesInPathWithIDPrefix    ($theme_dir . "/metaboxes",       "metabox_styles");
    }

    function wp_enqueue_scripts () {
        wp_enqueue_script("jquery");

        loadScriptsInPathWithIDPrefix   (WP_PLUGIN_DIR . "/Wordpress-CCK/frontend_libraries", "core_frontend_libraries");
        loadScriptsInPathWithIDPrefix   (WP_PLUGIN_DIR . "/Wordpress-CCK/frontend_scripts",   "core_frontend_scripts");

        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        loadScriptsInPathWithIDPrefix   ($theme_dir . "/scripts",   "theme_frontend_scripts");

    }

    function wp_enqueue_styles () {
        $theme      = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        loadStylesInPathWithIDPrefix    (WP_PLUGIN_DIR . "/Wordpress-CCK/frontend_styles",    "core_frontend_styles");
        loadStylesInPathWithIDPrefix    ($theme_dir . "/styles",       "theme_frontend_styles");

    }

    static function nav_menu_set_current($items, $menu, $args) {
        global $post;

        if ( !$post )
            return $items;

        $post_type = get_post_type();

        if ($post_type == 'page')
            return $items;

        $ancestor_ids   = array();
        $parent_ids     = array();

        foreach ($items as &$nav_item)
            if (self::menu_is_current_nav_item ($nav_item)) {
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
    static function menu_is_current_nav_item($nav_item) {
        global $post;
        global $wpc_content_types;

        $post_type = get_post_type();

        if ( !empty($post_type) && !is_archive() && !empty($wpc_content_types[$post_type]) ){
            $type = $wpc_content_types[$post_type];

            if ( !empty($type->menu_item_url) )
                $type_menu_url  = $type->menu_item_url;
            else {
                $r = the_record();
                $type_menu_url  = $r->menu_item_url;
            }

            if ( !empty($type_menu_url) ) {
                $type_menu_url  = home_url($type_menu_url);
                $nav_item_url   = preg_replace("/\/$/", "", $nav_item->url);

                return $type_menu_url == $nav_item_url;
            }
        }

        return false;
    }

    /**
     * autoload files in this directory as well as in fields/
     *
     * With namespaces, this would not be necessary.
     */
    static public function autoload($name) {
        if (@file_exists(dirname(__FILE__)."/$name.php"))
            include_once dirname(__FILE__)."/$name.php";
        else if (@file_exists(dirname(__FILE__)."/fields/$name.php"))
            include_once dirname(__FILE__)."/fields/$name.php";
    }
}

$WPCustom = new WPCustom();
?>
