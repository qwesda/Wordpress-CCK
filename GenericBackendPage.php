<?php

abstract class GenericBackendPage {
    public $backendpage_id  = NULL;
    public $label           = NULL;
    public $parent_menu_id  = NULL;
    public $capability      = 'manage_options';

    static $known_parents_initialized   = false;
    static $known_parents               = array(
        'index.php',
        'edit.php',
        'upload.php',
        'link-manager.php',
        'edit-comments.php',
        'themes.php',
        'plugins.php',
        'users.php',
        'tools.php',
        'options-general.php'
    );

    function __construct () {
        if ( !self::$known_parents_initialized ) {
            self::$known_parents_initialized = true;

            $post_types = get_post_types();

            foreach ($post_types as $post_type ) {
                array_push(self::$known_parents, "edit.php?post_type=$post_type");
            }
        }

    //  SET DEFAULTS
        if(empty($this->backendpage_id))        $this->backendpage_id   = strtolower(get_class_name($this));
        if(empty($this->parent_menu_id))        $this->parent_menu_id   = $this->backendpage_id;
        if(empty($this->label))                 $this->label            = strtolower(str_replace("_", " ", $this->backendpage_id));

        add_action('admin_menu', array($this, 'admin_menu') );
    }

    function admin_menu () {
        if ( in_array($this->parent_menu_id, self::$known_parents)) {
            add_submenu_page($this->parent_menu_id, $this->label, $this->label, $this->capability, $this->backendpage_id, array($this, 'echo_backend_page'));
        } else {
            add_menu_page($this->label, $this->label, $this->capability, $this->backendpage_id, array($this, 'echo_backend_page'));

            array_push(self::$known_parents, $this->backendpage_id);
        }
    }

    function echo_backend_page () {

    }
}

?>
