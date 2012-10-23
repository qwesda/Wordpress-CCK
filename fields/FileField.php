<?php

/**
 *
 */

class FileField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);

        $this->key    = "$parent->id-$this->id";

        add_filter('attachment_fields_to_edit',  array(&$this, 'add_tb_button'), 20, 2);
        add_action("wp_ajax_set-$this->key",     array(&$this, 'ajax_set'));
    }

    public function ajax_set () {
        global $post_id;

        $post_id = intval($_POST['post_id']);

        if ( !current_user_can('edit_post', $post_id))
            die('-1');

        $file_id = intval($_POST['file_id']);

        check_ajax_referer("set-$this->key");


        if ($file_id == '-1') {
            delete_post_meta($post_id, $this->id);

            die($this->get_field_core());
        }

        if ($file_id && get_post($file_id)) {
            update_post_meta($post_id, $this->id, $file_id);
            die($this->get_field_core_for_file_id($file_id));
        }

        die('0');
    }

    public function add_tb_button($form_fields, $file) {
        global $wpc_content_types;

        if (!empty($_GET['post_id']))
            $post_id = absint($_GET['post_id']);
        elseif (!empty($_POST))
            $post_id = $file->post_parent;

        if (!empty($post_id)) {
             $post_type = get_post_type($post_id);

             if ( !empty($wpc_content_types[$post_type]) && $this->parent->id == $post_type) {
                 foreach ($wpc_content_types[$post_type]->fields as $field_key => $field_def) {
                    if ($field_def->type == "FileField" || $field_def->type == "ImageField") {
                        $ajax_nonce    = wp_create_nonce("set-$this->key");
                        $link          = "<input class='wpc_input_file_set $this->key button' id='$this->key-$file->ID' href='#' type='button' onclick='WPCFileFiedSet(\"$this->key\", \"$post_id\", \"$file->ID\", \"$ajax_nonce\") ' value='Set as $this->label' /";

                        $form_fields[$this->key] = array(
                            'label' => $this->label,
                            'input' => 'html',
                            'html' => $link);
                     }
                 }
             }
             global $wpc_content_types;
        }

        return $form_fields;
    }

    function get_preview_html ($file_id) {
        $ret = "";

        if (!empty($file_id) && $file_id != "-1") {
            $att = get_post($file_id);

            $ret .= "<a target='_blank' class='wpc_file_field_file_name' href='$att->guid'>$att->post_title</a><br>";
            $ret .= "<span class='wpc_file_field_mime_type'>$att->post_mime_type</span><br>";
            $ret .= "<span class='wpc_file_field_date'>$att->post_date_gmt</span>";
        } else {
            $ret .= "<span class='wpc_file_field_not_set'>not set</span>";
        }

        return $ret;
    }

    function get_field_core_for_file_id ($file_id) {
        $ret = "<div id='wpc_file_field_container_$this->key'><div id='wpc_file_field_preview_$this->key' class='$this->key wpc_file_field_preview'>".
            $this->get_preview_html( $file_id ) . "
        </div>
        <input type='button' class='button wpc_input wpc_input_file_select' id='' value='select' />
        <input type='hidden' name='wpc_$this->id' value='$file_id' id='wpc_field_$this->id'>";

        if(!empty($file_id)){
            $ret .= "<input type='button' class='button wpc_input wpc_input_file_remove wpc_input_file_remove_$this->key'
                value='remove'
                data-nonce='" . wp_create_nonce("set-$this->key") . "'
                data-file_field_key='$this->key'
                data-file_id='$file_id' />";
        }

        $ret .= "</div>";

        return $ret;
    }
    function get_field_core ($with_default_value) {
        $ret    = "";
        $record = the_record();

        if (!empty($record) && !$with_default_value)
            $value  = $record->get($this->id);

        $file_id = !empty($value) ? intval($value) : '';

        return $this->get_field_core_for_file_id($file_id);
    }
    function echo_field_core ($with_default_value = false) {
        echo $this->get_field_core($with_default_value);
    }
}

?>
