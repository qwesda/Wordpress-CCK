<?php

/**
 *
 */

class ImageField extends FileField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    function get_preview_html ($file_id) {
        $ret = "";

        if (!empty($file_id) && $file_id != "-1") {
            $att         = get_post($file_id);
            $meta        = (object) wp_get_attachment_metadata($file_id);
            $thumb_url   = wp_get_attachment_thumb_url($file_id);
                        
            $ret .= "<img src='$thumb_url'>";
            $ret .= "<a target='_blank' class='wpc_file_field_file_name' href='$att->guid'>$att->post_title</a><br>";
            $ret .= "<span class='wpc_file_field_mime_type'>$att->post_mime_type</span><br>";
            $ret .= "<span class='wpc_file_field_date'>$att->post_date_gmt</span>";
            $ret .= "<span class='wpc_file_field_width'>$meta->width</span>px * <span class='wpc_file_field_height'>$meta->height px</span>";
        } else {
            $ret .= "<span class='wpc_file_field_not_set'>not set</span>";
        }

        return $ret;
    }
}

?>