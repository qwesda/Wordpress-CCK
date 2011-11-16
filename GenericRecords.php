<?php

/**
 * records
 */
class GenericRecords extends RecordList {

    protected $table_alias = "posts";
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
     * returns an instance of (a subclass of) GenericRecords for the specific type.
     * if $id is set to a valid id of type $type, prefilter to get only connected relations.
     */
    static function records_for_type($type, $id=null) {
        global $wpc_content_types;

        if (! isset($wpc_content_types[$type])) {
            // XXX: there should be an _error here!
            _log("The type $type does not exist in the database.");
            return null;
        }

        $classname = ucfirst($type)."Records";
        if (! class_exists($classname)){
            $classdef = "class $classname extends ".__CLASS__." {
            }";
            eval ($classdef);
        }

        $records = new $classname();

        if ($id !== null)
            $records = $records->id_is($id);

        return $records;
    }

    /**
     * Prepares the object to iterate over the results. Resets the iteration pointer.
     */
    function iterate ($as='OBJECT') {
        // do only get results the first time it is called
        if (! isset($this->iterate_results))
            $this->iterate_results = $this->results($as);
        $this->iterate_pointer = 0;
        return $this;
    }

    /**
     * iterate over the fetched results (in iterate())
     * return false, if there are no more results.
     */
    function next() {
        // although against API, support next() w/o previous iterate().
        if (!isset ($this->iterate_results))
            $this->iterate();

        if (count($this->iterate_results) <= $this->iterate_pointer)
            return false;

        return $this->iterate_results[$this->iterate_pointer++];
    }

    /**
     * returns all filtered records as array.
     */
    function results() {
        return array_map(array($this, "row_to_record"), parent::results());
    }
    protected function row_to_record($record) {
        return GenericRecord::new_type($record[$this->table_pk], null, $record[$this->table], $record["meta"]);
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
