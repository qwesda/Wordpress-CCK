<?php

/**
 * records
 */
class WPCRecordCollection extends WPCCollection {

    protected $table_alias = "posts";

    /**
     * these are the post's main cols (wp)
     */
    protected $table_cols = array('id', 'post_author', 'post_date',
        'post_date_gmt', 'post_content', 'post_content_filtered',
        'post_title', 'post_excerpt', 'post_status', 'post_type',
        'comment_count', 'comment_status', 'ping_status', 'post_password',
        'post_name', 'to_ping', 'pinged', 'post_modified',
        'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type',
        'guid');
    protected $table_pk = "ID";
    protected $meta_fk = "post_id";

    function __construct () {
        global $wpdb;
        $this->table = $wpdb->posts;
        $this->meta_table = $wpdb->postmeta;
    }

    /**
     * returns an instance of (a subclass of) WPCRecordCollection for the specific type.
     * if $id is set to a valid id of type $type, prefilter to get only connected relations.
     */
    static function records_for_type($type, $id=null) {
        global $wpc_content_types;

        if (! isset($wpc_content_types[$type])) {
            ButterLog::error("The type $type does not exist in the database.");
            return null;
        }

        $classname = ucfirst($type)."Records";
        if (! class_exists($classname)){
            $classdef = "class $classname extends ".__CLASS__." {
            }";
            eval ($classdef);
        }

        $records = new $classname();

		$records = $records->filter("post_type", $type);

        if ($id !== null)
            $records = $records->id_is($id);

        return $records;
    }

    /**
     * returns all filtered records as array.
     */
    function results() {
        return array_map(array($this, "row_to_record"), parent::results());
    }
    function row_to_record($record) {
        return WPCRecord::new_record($record[$this->table_pk], $record[$this->table], $record["meta"]);
    }

    /**
     * convenience method for filter. filters for the id.
     * returns a new instance.
     */
    function id_is($id) {
        return $this->filter("id", $id);
    }

    function add_filter_($key, $val, $op="=") {
        $key = strtolower($key);
        parent::add_filter_($key, $val, $op);
    }
}
?>
