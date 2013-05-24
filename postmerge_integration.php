<?php

$wpc_pm_hidden_fields = array('ID', 'post_date', 'post_date_gmt', 'post_title',
    'post_status', 'comment_status', 'ping_status', 'post_modified',
    'post_modified_gmt', 'guid', 'post_type', 'filter', 'post_id',
);

add_filter('pm_change_data', function($data, $original_data) {
    $rec = the_record($data['ID']);
    return array_merge($data, $rec->as_array());
}, 10, 2);

# don't display internal fields
add_filter('pm_hidden_field', function($hidden, $field) use ($wpc_pm_hidden_fields) {
    global $wpdb;

    $hidden = in_array($field, $wpc_pm_hidden_fields);
    return in_array($field, $wpc_pm_hidden_fields);
}, 10, 2);

add_action('pm_save_post', function($id, $old_ids, $post, $meta) {
    global $wpdb;

    $rec = the_record($id);
    $type = $rec->type;
    array_walk($meta, function($val, $key) use ($rec) {
        $rec->set($key, $val);
    });
    $rec->commit(true);


    // add metadata to new record

    $id_sql_prep_s = implode(", ", array_fill(0, count($old_ids), '%d'));
    $data = $old_ids;
    array_unshift($data, $id);

    foreach ($type->relationships as $rel) {
        $key_cols = array();
        if ($rel->post_type_to_id == $type->id)
            $key_cols[] = "post_to_id";
        if ($rel->post_type_from_id == $type->id)
            $key_cols[] = "post_from_id";

        foreach ($key_cols as $col) {
            ButterLog::debug($wpdb->prepare("UPDATE `$rel->table` SET `$col` = %d
                WHERE `$col` IN ($id_sql_prep_s);", $data));
            $wpdb->query($wpdb->prepare("UPDATE `$rel->table` SET `$col` = %d
                WHERE `$col` IN ($id_sql_prep_s);", $data));
        }
    }
}, 10, 4);
?>
