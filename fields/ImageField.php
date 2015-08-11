<?php

/**
 *
 */

class ImageField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);

        $this->key    = $parent->id."-".$this->id;

        add_filter('attachment_fields_to_edit',  array(&$this, 'add_tb_button'), 20, 2);
        add_action("wp_ajax_set-$this->key",     array(&$this, 'ajax_set'));

        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('media-upload');
    }

    public function ajax_set () {
        global $post_id;

        $post_id = intval($_POST['post_id']);

        if ( !current_user_can('edit_post', $post_id))
            die('-1');

        $image_id = intval($_POST['image_id']);

        check_ajax_referer("set-$this->key");


        if ($image_id == '-1') {
            delete_post_meta($post_id, $this->id);

            die($this->get_field_core());
        }

        if ($image_id && get_post($image_id)) {
            update_post_meta($post_id, $this->id, $image_id);
            die($this->get_field_core_for_image_id($image_id));
        }

        die('0');
    }

    public function add_tb_button($form_fields, $image) {
        global $wpc_content_types;

        if (!empty($_GET['post_id']))
            $post_id = absint($_GET['post_id']);
        elseif (!empty($_POST))
            $post_id = $image->post_parent;

        if (!empty($post_id)) {
             $post_type = get_post_type($post_id);

             if ( !empty($wpc_content_types[$post_type]) && $this->parent->id == $post_type) {
                 foreach ($wpc_content_types[$post_type]->fields as $field_key => $field_def) {
                    if ($field_def->type == "ImageField") {
                        $ajax_nonce    = wp_create_nonce("set-$this->key");
                        $link          = "<input class='wpc_input_image_set $this->key button' id='$this->key-$image->ID' href='#' type='button' onclick='WPCImageFiedSet(\"$this->key\", \"$post_id\", \"$image->ID\", \"$ajax_nonce\") ' value='Set as $this->label' /";

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

    function get_preview_html ($image_id) {
        $ret = "";

        if (!empty($image_id) && $image_id != "-1") {
            $att         = get_post($image_id);
            $meta        = (object) wp_get_attachment_metadata($image_id);
            $thumb_url   = wp_get_attachment_thumb_url($image_id);

            $ret .= "<img src='$thumb_url'>";
            $ret .= "<a target='_blank' class='wpc_image_field_image_name' href='$att->guid'>$att->post_title</a><br>";
            $ret .= "<span class='wpc_image_field_mime_type'>$att->post_mime_type</span><br>";
            $ret .= "<span class='wpc_image_field_date'>$att->post_date_gmt</span>";
            $ret .= "<span class='wpc_image_field_width'>$meta->width</span>px * <span class='wpc_image_field_height'>$meta->height px</span>";
        } else {
            $ret .= "<span class='wpc_image_field_not_set'>not set</span>";
        }

        return $ret;
    }
    function get_field_core_for_image_id ($image_id) {
        $ret = "<div id='wpc_image_field_container_$this->key'><div id='wpc_image_field_preview_$this->key' class='$this->key wpc_image_field_preview'>".
            $this->get_preview_html( $image_id ) . "
        </div>
        <input type='button' class='button wpc_input wpc_input_image_select' id='' value='select' />
        <input type='hidden' name='wpc_$this->id' value='$image_id' id='wpc_field_$this->id'>";

        if(!empty($image_id)){
            $ret .= "<input type='button' class='button wpc_input wpc_input_image_remove wpc_input_image_remove_$this->key'
                value='remove'
                data-nonce='" . wp_create_nonce("set-$this->key") . "'
                data-image_field_key='$this->key'
                data-image_id='$image_id' />";
        }

        $ret .= "</div>";

        return $ret;
    }
    function get_field_core ($with_default_value = false) {
        $ret    = "";
        $record = the_record();

        if (!empty($record) && !$with_default_value)
            $value  = $record->get($this->id);

        $image_id = !empty($value) ? intval($value) : '';

        return $this->get_field_core_for_image_id($image_id);
    }
    function echo_field_core ($with_default_value = false) {
        echo $this->get_field_core($with_default_value);
    }
}

?>
