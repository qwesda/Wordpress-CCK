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
    public $show_in_menu        = true;

    public $menu_item_url       = "";

    protected $is_first_metabox   = true;

    // is this still neccessary?
    protected $current_post_data  = array();

    public $table;
    public $id_col;
    public $wpid_col;


    public static $wp_keys = array('post_author', 'post_date',
        'post_date_gmt', 'post_content', 'post_content_filtered',
        'post_title', 'post_excerpt', 'post_status', 'post_type',
        'comment_count', 'comment_status', 'ping_status', 'post_password',
        'post_name', 'to_ping', 'pinged', 'post_modified',
        'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type',
        'guid');


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
                'show_in_menu'          => $this->show_in_menu,
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
        add_filter("manage_{$this->slug}_posts_custom_column",
            array($this, "wp_manage_posts_custom_column"), 10, 2);
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

    protected function get_field_type ($field_key) {
        $ret = "";

        if ( !empty($this->fields[$field_key]) )
            $ret = $this->fields[$field_key]->type;

        return $ret;
    }

    public function echo_update_relation_item_metabox_str () {
        ob_start();

        global $post;
        $old_post = $post;
        $post = null;

        $this->echo_update_relation_item_metabox();

        $html_str = ob_get_clean();
        $html_str = preg_replace("/(id|for|name)\=('|\")wpc_/", "$1=$2wpc_".$this->id."_", $html_str);
        $html_str = htmlspecialchars($html_str);
        $html_str = preg_replace("/\n/", "\\\n", $html_str);

        $post = $old_post;

        return $html_str;
    }

    public function echo_new_relation_item_metabox_str () {
        ob_start();

        global $post;
        $old_post = $post;
        $post = null;

        $this->echo_new_relation_item_metabox();

        $html_str = ob_get_clean();
        $html_str = preg_replace("/(id|for|name)\=('|\")wpc_/", "$1=$2wpc_".$this->id."_", $html_str);
        $html_str = htmlspecialchars($html_str);
        $html_str = preg_replace("/\n/", "\\\n", $html_str);

        $post = $old_post;

        return $html_str;
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

        $ret = $cols + $manage_cols;

        // move date to end of array
        if ( !empty($ret['date']) ) {
            $date_val = $ret['date'];
            unset($ret['date']);
            $ret['date'] = $date_val;
        }

        return $ret;
    }

    /**
     * wp callback to display columns in overview for this post type
     */
    public function wp_manage_posts_custom_column($column, $id) {
        // all columns with key edit_column
        $manage_cols = array_filter($this->fields, function($col) {
            return $col->edit_column;
        });
        if (! isset($manage_cols[$column]))
            return;

        $element = the_record($id);


        echo $element->get($column);
    }

    /**
     * wp callback to sort columns in overview for this post type
     */
    public function wp_manage_edit_sortable_columns($cols) {
        // all columns with key edit_column
        $manage_cols = array_keys(array_filter($this->fields, function($col) {
            return $col->sortable_column;
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

                function disable_posttitle () {
                    if ( <?php echo (!empty($this->generated_values['post_title']) ? "true" : "false"); ?> ) {

                        jQuery('input#title').attr("disabled", "disabled");
                    }
                    if ( <?php echo (!empty($this->generated_values['post_name']) ? "true" : "false"); ?> ) {
                        jQuery('#edit-slug-buttons a').attr("disabled", "disabled");
                        jQuery('#edit-slug-buttons a').removeAttr("onclick");
                        jQuery('#editable-post-name').unbind("click");
                    }
                }

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

                    disable_posttitle();
                });

            </script>

        <?php

        }
    }

    function admin_init() {
    }

    function delete_post ($post_id, $post=null) {
        global $wpdb;
        global $wpc_relationships;

        #ButterLog::debug("deletion post with post_id $post_id");

        $post_type = get_post_type($post_id);

        if ( $wpdb->query( $wpdb->prepare("DELETE FROM $this->table WHERE post_id = %d", $post_id) ) === FALSE) {
            ButterLog::error("Could not delete wpc data for $post_id in table $this->table.");
            return false;
        }

        if( !empty($post_type) ) foreach ($wpc_relationships as $wpc_relationship) {
            if ( $post_type == $wpc_relationship->post_type_from_id ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM $wpc_relationship->table WHERE post_from_id = %d", $post_id) );
            }

            if ( $post_type == $wpc_relationship->post_type_to_id ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM $wpc_relationship->table WHERE post_to_id = %d", $post_id) );
            }
        }
        return true;
    }

    function create_post ($post = array(), $wpcpost = array(), $write_read_only_fields = false) {
        #ButterLog::debug("creating new $this->id");

        $post['post_type'] = $this->id;

        $post_id = intval( wp_insert_post($post) );

        if ( !empty($post_id) ) {
            $this->update_post($post_id, $post, $wpcpost, $write_read_only_fields);
        }

        return $post_id;
    }

    function new_post ($post_id, $post, $wpcpost) {
        global $wpdb;

        #ButterLog::debug("new post with post_id $post_id");

        $data = array($this->wpid_col => $post_id);

        if (! $wpdb->insert($this->table, $data, '%d')) {
            ButterLog::error("Could not insert initial row for $post_id.");
        }

        // this might be unneccessary.
        // custom fields might always be unset at this point
        // look for 'new post' not followed by 'nothing to save' log messages
        $this->update_post($post_id, $post, $wpcpost);
    }

    function update_post ($post_id, $post, $wpcpost, $write_read_only_fields = false) {
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
        $to_update = array_intersect_key($wpcpost, $candidate_fields);

        if (! empty($to_update))
            $this->update_dbs($post_id, $to_update, $field_formats);

        // regenerate GeneratedValues
        $to_update = array_map(function ($field) use ($post_id) {
            return $field->value_uncached($post_id);
        }, $this->generated_values);

        $field_formats = array_map(function ($field) {
            return $field->printf_specifier;
        }, $this->generated_values);

        $post_status    = 'publish';
        $post_type      = $this->id;
        $post_parent    = 0;

        if ( !empty($this->generated_values["post_name"]) )         $unsanatized_post_slug = $to_update["post_name"];
        elseif ( !empty($this->generated_values["post_title"]) )    $unsanatized_post_slug = $to_update["post_title"];

        if ( !empty($unsanatized_post_slug) ) {
            $to_update["post_name"]     = wp_unique_post_slug(
                                            sanitize_title(str_replace("/", "-", str_replace(array("ä","ö","ü","ß"), array("ae","oe","ue","ss"), strtolower($unsanatized_post_slug)))),
                                            $post_id,
                                            $post_status,
                                            $post_type,
                                            $post_parent
                                        );

            $field_formats["post_name"] = "%s";
        }

        $this->update_dbs($post_id, $to_update, $field_formats);
    }

    protected function update_dbs ($post_id, $to_update, $field_formats) {
        global $wpdb;

        #ButterLog::debug("update_dbs $post_id.", $to_update);
        #ButterLog::debug("update_dbs $post_id.", $field_formats);

        $wp_keys = array_flip(self::$wp_keys);

        $this->update_db($this->table, $post_id,
            array_diff_key($to_update, $wp_keys), $field_formats);

        $this->update_db($wpdb->posts, $post_id,
            array_intersect_key($to_update, $wp_keys), $field_formats);
    }

    protected function update_db($table, $post_id, $to_update, $field_formats) {
        global $wpdb;

        if (empty($to_update))
            return true;

        $formats    = array_intersect_key($field_formats, $to_update);

        $id_row             = $table == $wpdb->posts ? "ID" : $this->wpid_col;
        $query              = "SELECT $id_row FROM $table WHERE $id_row = $post_id";
        $table_entry_test   = $wpdb->get_var( $query );

        if ( empty( $table_entry_test ) ) {
            $formats[$id_row]   = "%d";
            $to_update[$id_row] = $post_id;

            if ($wpdb->insert($table,
                $to_update,                         // col = val
                $formats,                           // printf formats for set
                '%d'                                // printf format for where
                ) === false) {
                    ButterLog::error("Could not update $table for post_id $post_id.");
                    return false;
            }
        } else {
            if ($wpdb->update($table,
                $to_update,                         // col = val
                array( ($table == $wpdb->posts ? "ID" : $this->wpid_col) => $post_id), // where
                $formats,                           // printf formats for set
                '%d'                                // printf format for where
                ) === false) {
                    ButterLog::error("Could not update $table for post_id $post_id.");
                    return false;
            }
        }



        return true;
    }
}

?>
