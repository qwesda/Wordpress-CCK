<?php

global $wpc_content_types;
$wpc_content_types = array();

abstract class GenericContentType {
    public $id                  = NULL;
    public $fields              = array();
    public $generated_values    = array();
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
        if ( empty($this->table) )          $this->table            = "wp_wpc_$this->id";
        if ( empty($this->id_col) )         $this->id_col           = 'meta_id';
        if ( empty($this->wpid_col) )       $this->wpid_col         = 'post_id';

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
                'register_meta_box_cb'  => array($this, 'wp_register_meta_box')
            ) );
        }

//  ADD HOOKS
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

        WPCRecord::make_specific_class(ucfirst($this->id)."Record", "$this->id");

        $wpc_content_types[$this->id] = $this;
    }

    /**
     * stub which does nothing. overwrite if needed
     */
    public function wp_register_meta_box () {

    }

    public function echo_update_relation_item_metabox () {
        return $this->echo_new_relation_item_metabox();
    }

    public function echo_new_relation_item_metabox () {
        return "";
    }

    public function echo_update_relation_item_metabox_str () {
        ob_start();

        $this->echo_update_relation_item_metabox();

        $html_str = ob_get_clean();
        $html_str = str_replace("id=\"wpc", "id=\"wpc_$this->id", $html_str);
        $html_str = htmlspecialchars($html_str);

        return htmlspecialchars($html_str);
    }

    public function echo_new_relation_item_metabox_str () {
        ob_start();

        $this->echo_new_relation_item_metabox();

        $html_str = ob_get_clean();
        $html_str = str_replace("id=\"wpc", "id=\"wpc_$this->id", $html_str);
        $html_str = htmlspecialchars($html_str);

        return htmlspecialchars($html_str);
    }

    /**
     * stub which does nothing. overwrite if needed
     */
    public function wp_insert_post_data($data, $postarr) {
        return $data;
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

    function delete_post ($post_id, $post) {
        global $wpdb;
        global $wpc_relationships;

        #ButterLog::debug("deletion post with post_id $post_id");

        $post_type = get_post_type($post_id);

        if ( $wpdb->query( $wpdb->prepare("DELETE FROM $this->table WHERE post_id = %d", $post_id) ) === FALSE) {
            ButterLog::error("Could not delete postmeta for $post_id in table $this->table.");
        }

        if( !empty($post_type) ) foreach ($wpc_relationships as $wpc_relationship) {
            if ( $post_type == $wpc_relationship->post_type_from_id ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM $wpc_relationship->table WHERE post_from_id = %d", $post_id) );
            }

            if ( $post_type == $wpc_relationship->post_type_to_id ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM $wpc_relationship->table WHERE post_to_id = %d", $post_id) );
            }
        }
    }

    function create_post ($post = array(), $postmeta = array(), $write_read_only_fields = false) {
        #ButterLog::debug("creating new $this->id");

        $post['post_type'] = $this->id;

        $post_id = intval( wp_insert_post($post) );

        if ( !empty($post_id) ) {
            $this->update_post($post_id, $post, $postmeta, $write_read_only_fields);
        }

        return $post_id;
    }

    function new_post ($post_id, $post, $postmeta) {
        global $wpdb;

        #ButterLog::debug("new post with post_id $post_id");

        $data = array($this->wpid_col => $post_id);

        if (! $wpdb->insert($this->table, $data, '%d')) {
            ButterLog::error("Could not insert initial row for $post_id.");
        }

        // this might be unneccessary.
        // custom fields might always be unset at this point
        // look for 'new post' not followed by 'nothing to save' log messages
        $this->update_post($post_id, $post, $postmeta);
    }

    function update_post ($post_id, $post, $postmeta, $write_read_only_fields = false) {
        #ButterLog::debug("saving post with post_id $post_id");

        if (! $write_read_only_fields) {
            $candidate_fields = array_filter($this->fields,
                function($field) use ($post_id) {
                    return $field->may_write($post_id);
            });
        } else {
            $candidate_fields = $this->fields;
        }

        $field_defaults = array_map(function ($field) {
            return $field->default;
        }, $candidate_fields);

        $field_formats = array_map(function ($field) {
            return $field->printf_specifier;
        }, $candidate_fields);

        // weed out invalid fields, add defaults
        // XXX: handle unsetting fields
        // see https://core.trac.wordpress.org/ticket/15158
        $to_update = array_filter(wp_parse_args(array_filter($postmeta), $field_defaults));

        if (empty($to_update)) {
            #ButterLog::debug("Nothing to save for post $post_id.");
            return;
        }

        $this->update_dbs($post_id, $to_update, $field_formats);

        // regenerate GeneratedValues
        $to_update = array_map(function ($field) use ($post_id) {
            return $field->value_uncached($post_id);
        }, $this->generated_values);

        $field_formats = array_map(function ($field) {
            return $field->printf_specifier;
        }, $this->generated_values);

        $this->update_dbs($post_id, $to_update, $field_formats);
    }

    protected function update_dbs ($post_id, $to_update, $field_formats) {
        global $wpdb;

        #ButterLog::debug("update_dbs $post_id.", $to_update);
        #ButterLog::debug("update_dbs $post_id.", $field_formats);

        $wp_fields = array_flip(array('post_author', 'post_date',
            'post_date_gmt', 'post_content', 'post_content_filtered',
            'post_title', 'post_excerpt', 'post_status', 'post_type',
            'comment_count', 'comment_status', 'ping_status', 'post_password',
            'post_name', 'to_ping', 'pinged', 'post_modified',
            'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type',
            'guid'));

        $this->update_db($this->table, $post_id,
            array_diff_key($to_update, $wp_fields), $field_formats);

        $this->update_db($wpdb->posts, $post_id,
            array_intersect_key($to_update, $wp_fields), $field_formats);
    }

    protected function update_db($table, $post_id, $to_update, $field_formats) {
        global $wpdb;

        if (empty($to_update))
            return true;

        $formats = array_intersect_key($field_formats, $to_update);

        if ($wpdb->update($table,
            $to_update,                         // col = val
            array( ($table == $wpdb->posts ? "ID" : $this->wpid_col) => $post_id), // where
            $formats,                           // printf formats for set
            '%d'                                // printf format for where
            ) === false) {
                ButterLog::error("Could not update $table for post_id $post_id.");
                return false;
        }
        return true;
    }
}

?>
