<?php

global $wpc_content_types;
$wpc_content_types = array();

abstract class GenericContentType {
    public $id                  = NULL;
    public $fields              = array();
    public $relationships       = array();

    public $label               = "";
    public $slug                = "";
    public $singular_label      = "";
    public $supports            = array('title','editor');
    public $has_archive         = false;
    public $hierarchical        = false;
    public $menu_position       = 5;

    private $is_first_metabox   = true;
    private $current_post_data  = array();

    function __construct () {
        global $wpdb;
        global $wpc_content_types;

//  SET DEFAULTS
        if ( empty($this->id) )             $this->id               = strtolower ( get_class_name($this) );

        if ( empty($this->label) )          $this->label            = $this->id . "s";
        if ( empty($this->singular_label) ) $this->singular_label   = $this->id;
        if ( empty($this->slug) )           $this->slug             = $this->id;

        if ( empty($this->taxonomies) )     $this->taxonomies       = array ();

//  REGISTER POST TYPE
        if(!post_type_exists($this->id)) {
            register_post_type ($this->id, array(
                'label'                 => ucfirst($this->label),
                'singular_label'        => ucfirst($this->singular_label),
                'public'                => true,
                'show_ui'               => true,
                'capability_type'       => 'post',
                'hierarchical'          => $this->hierarchical,
                'menu_position'         => $this->menu_position,
                '_builtin'              => false,
                'rewrite'               => array("slug" => $this->slug),
                'query_var'             => $this->slug,
                'supports'              => $this->supports,
                'has_archive'           => $this->has_archive,
                'taxonomies'            => $this->taxonomies,
                #'register_meta_box_cb'  => array(&$this, "add_meta_boxes")
            ) );
        }

//  ADD HOOKS
        add_action ("save_post",                    array(&$this, "save_post") );
        add_action ("wp_insert_post",               array(&$this, "wp_insert_post") );
        add_action ("wp_update_post",               array(&$this, "wp_update_post") );
    //  add_action ("delete_post",                  array(&$this, "delete_post") ); // NOT ACTUALLY NEEDED - RELATED POSTMETA GETS DELETED AUTOMATICALLY

        add_action ('admin_print_scripts',          array($this, "custom_print_scripts") );
        add_action ('admin_print_styles',           array($this, "custom_print_styles") );

        add_filter("manage_edit-{$this->slug}_columns",
            array(&$this, "wp_manage_edit_columnms"));
        add_filter("manage_edit-{$this->slug}_columns",
            array(&$this, "wp_manage_edit_columnms"));
        add_filter("manage_edit-{$this->slug}_sortable_columns",
            array(&$this, "wp_manage_edit_sortable_columns"));

        add_filter( "the_content",  array($this, "the_content") );

        WPCRecord::make_specific_class(ucfirst($this->id)."Record", "$this->id");
    }

    /**
     * wp callback to add columns in overview for this post type
     */
    public function wp_manage_edit_columns($cols) {
        $manage_cols = array_keys(array_filter($this->cols, function($col) {
            return isset($col['edit_column']) && $col['edit_column'] == true;
        }));

        $manage_cols = array_combine($manage_cols, $manage_cols);

        return $cols + $manage_cols;
    }

    /**
     * wp callback to display columns in overview for this post type
     */
    public function wp_manage_edit_columns_display($col, $id) {
        // all columns with key edit_column
        $manage_cols = array_filter($this->cols, function($col) {
            return isset($col['edit_column']) && $col['edit_column'] == true;
        });
        if (! isset($manage_cols[$col]))
            return;

        $element = $this->element_by_wp_id($id);
        echo $element->formatted_string($col);
    }

    /**
     * wp callback to sort columns in overview for this post type
     */
    public function wp_manage_edit_sortable_columns($cols) {
        // all columns with key edit_column
        $manage_cols = array_keys(array_filter($this->cols, function($col) {
            return isset($col['edit_column']) && $col['edit_column'] == true;
        }));

        // [$col => $col]
        $manage_cols = array_combine($manage_cols, $manage_cols);

        return $cols + $manage_cols;
    }

    function the_content ($input_content) {
        global $post;
        global $content;

        $content = $input_content;

        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        if(!empty($post)) foreach (glob("$theme_dir/content_overrides/" . $post->post_type . ".php") as $filename) {
            if ($post->post_type == $this->id) {

                $content = _compile($filename);
            }
        }

        return $content;
    }

    function custom_print_scripts () {
    }

    function custom_print_styles () {

    }

    function first_metabox () {
        if ($this->is_first_metabox == true) {
            $this->is_first_metabox = false;
        ?>
            <script type="text/javascript">
                var postID = <?php the_ID(); ?>;

                function check_text_input_value(event) {
                    var input = jQuery(this);
                    var label = jQuery("label.wpc_hint[for='" + input.attr('id') + "']");

                    if ( !(event.type == "keydown" && event.keyCode != 9) && input.val() == "" ){
                        label.addClass("show_full");
                    } else if (    event.keyCode >= 48
                                && event.keyCode != 91
                                && event.keyCode != 93) {
                        label.removeClass("show_full");
                    }
                }

                jQuery(document).ready(function () {
                    jQuery("body").delegate(".wpc_input_text", "focus keydown keyup change", check_text_input_value);
                    jQuery(".wpc_input_text").each(check_text_input_value);
                });

            </script>

        <?php

        }
    }

    function admin_init() {

    }

    function load_post_data ($post) {
        $this->current_post_data = array();

        if( !empty($post) && $post->post_type == $this->id ) {
            $post_custom = get_post_custom($post->ID);

            foreach ($this->fields as $field_key => $field) {
                if ( !empty($post_custom[$field_key]) ) {
                    if ( sizeof($post_custom[$field_key]) == 1 ) {
                        $this->current_post_data[$field_key] = $post_custom[$field_key][0];
                    } else {
                        $this->current_post_data[$field_key] = $post_custom[$field_key];
                    }
                } elseif ( !empty($this->fields[$field_key]->default) ) {
                    $this->current_post_data[$field_key] = $this->fields[$field_key]->default;
                } else {
                    $this->current_post_data[$field_key] = "";
                }
            }

            return $this->current_post_data;
        }

        return false;
    }

    function delete_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            return true;
        }

        return false;
    }


    function wp_update_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            return true;
        }

        return false;
    }

    function save_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            $fields_to_update = array();
            $fields_to_remove = array();

            foreach ($this->fields as $field_key => $field) {
                if ( !empty($_POST["wpc_$field_key"]) ) {
                    $fields_to_update[$field_key] = $_POST["wpc_$field_key"];
                } elseif ( !empty($this->fields[$field_key]->default) ) {
                    $fields_to_update[$field_key] = $this->fields[$field_key]->default;
                } else {
                    $fields_to_remove[$field_key] = true;
                }
            }

            foreach ($fields_to_update as $field_key => $field_value) {
                update_post_meta($post_id, $field_key, $field_value);
            }

            foreach ($fields_to_remove as $field_key => $field_value) {
                delete_post_meta($post_id, $field_key);
            }

            return true;
        }

        return false;
    }

    function wp_insert_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {

            return true;
        }

        return false;
    }

}

?>
