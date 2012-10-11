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

    protected $is_first_metabox   = true;

    // is this still neccessary?
    protected $current_post_data  = array();

    public $table;
    public $id_col;
    public $wpid_col;

    function __construct () {
        global $wpc_content_types;

//  SET DEFAULTS
        if ( empty($this->id) )             $this->id               = strtolower ( get_class_name($this) );

        if ( empty($this->label) )          $this->label            = $this->id . "s";
        if ( empty($this->singular_label) ) $this->singular_label   = $this->id;
        if ( empty($this->slug) )           $this->slug             = $this->id;
        if ( empty($this->table) )          $this->table            = ucfirst($this->id . "s");
        if ( empty($this->id_col) )         $this->id_col           = 'meta_id';
        if ( empty($this->wpid_col) )       $this->wpid_col         = 'post_id';
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
                'register_meta_box_cb'  => array($this, 'wp_register_meta_box')
            ) );
        }

//  ADD HOOKS
        add_action ("wp_insert_post",               array($this, "wp_insert_post"), 10, 2);
        add_action ("wp_update_post",               array($this, "wp_update_post") );
        add_action ("delete_post",                  array($this, "delete_post") );

        add_action ('admin_print_scripts',          array($this, "custom_print_scripts") );
        add_action ('admin_print_styles',           array($this, "custom_print_styles") );

        add_filter("manage_edit-{$this->slug}_columns",
            array($this, "wp_manage_edit_columns"));
        add_filter("manage_edit-{$this->slug}_display",
            array($this, "wp_manage_edit_columns_display"));
        add_filter("manage_edit-{$this->slug}_columns",
            array($this, "wp_manage_edit_columns"));
        add_filter("manage_edit-{$this->slug}_sortable_columns",
            array($this, "wp_manage_edit_sortable_columns"));

        add_filter( "the_content",  array($this, "the_content") );

        WPCRecord::make_specific_class(ucfirst($this->id)."Record", "$this->id");

        $wpc_content_types[$this->id] = $this;
    }

    /**
     * stub which does nothing. overwrite if needed
     */
    public function wp_register_meta_box() {
    }

    /**
     * wp callback to add columns in overview for this post type
     */
    public function wp_manage_edit_columns($cols) {
        $manage_cols = array_keys(array_filter($this->fields, function($col) {
            return $col->edit_column;
        }));

        if (! empty($manage_cols))
            $manage_cols = array_combine($manage_cols, $manage_cols);

        return $cols + $manage_cols;
    }

    /**
     * wp callback to display columns in overview for this post type
     */
    public function wp_manage_edit_columns_display($column, $id) {
        // all columns with key edit_column
        $manage_cols = array_filter($this->fields, function($col) {
            return $col->sortable_column;
        });
        if (! isset($manage_cols[$column]))
            return;

        $element = $this->element_by_wp_id($id);
        echo $element->formatted_string($col);
    }

    /**
     * wp callback to sort columns in overview for this post type
     */
    public function wp_manage_edit_sortable_columns($cols) {
        // all columns with key edit_column
        $manage_cols = array_keys(array_filter($this->fields, function($col) {
            return $col->edit_column;
        }));

        // [$col => $col]
        if (! empty($manage_cols))
            $manage_cols = array_combine($manage_cols, $manage_cols);

        return $cols + $manage_cols;
    }

    function the_content ($input_content) {
        global $post;
        global $content;

        $content = $input_content;

        $theme  = wp_get_theme();
        $theme_dir  = $theme["Stylesheet Dir"];

        if (!empty($post) && $post->post_type == $this->id) {
            $filename = "$theme_dir/content_overrides/{$post->post_type}.php";
            if (file_exists($filename))
                $content = _compile($filename);
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

    function save_post ($post_id, $post) {
        global $wpdb;

        ButterLog::debug("saving post with post_id: $post_id");
        $fields_to_update = array();

        $candidate_fields = array_filter($this->fields,
            function($field) use ($post_id) {
                return $field->may_write($post_id);
            }
        );

        foreach ($candidate_fields as $field_key => $field) {
            if ( !empty($_POST["wpc_$field_key"]) ) {
                $fields_to_update[$field_key] = $_POST["wpc_$field_key"];
            } elseif ( !empty($this->fields[$field_key]->default) ) {
                $fields_to_update[$field_key] = $this->fields[$field_key]->default;
            } else {
                $fields_to_update[$field_key] = NULL;
            }
        }

        if( $wpdb->update($this->table,
            $fields_to_update,                  // col = val
            array($this->wpid_col => $post_id), // where
            array_map(function($col) {          // printf formats for set
                return $col->printf_specifier;
            }, $this->fields),
            '%d'                                // printf format for where
            ) === false) {
                ButterLog::error("Could not update data for post_id $post_id.");
                return false;
        }
        return true;
    }

    function wp_insert_post ($post_id, $post) {
        if( !empty($post) && $post->post_type == $this->id) {

            return true;
        }

        return false;
    }

}

?>
