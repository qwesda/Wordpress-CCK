<?php

/**
 * The base class for records of a content type.
 *
 * Does not lazy-load data.
 */
abstract class WPCRelation extends WPCData {
    protected $to_type;
    protected $from_type;

    protected $data_keys = array('id', 'record_from', 'record_to');

    /**
     * constructor for a Relation
     */
    protected function __construct($id, $record_from, $record_to, $meta=null) {
        global $wpc_content_types, $wpc_relationships;

        $this->id = $id;
        $this->to_type = $wpc_content_types[$wpc_relationships[$this->typeslug]->post_type_to_id];
        $this->from_type = $wpc_content_types[$wpc_relationships[$this->typeslug]->post_type_from_id];
        $this->meta_keys = array_keys($wpc_relationships[$this->typeslug]->fields);

        // if only the numerical id is given, construct a new record object
        if ($record_from && ! is_object($record_from))
            $record_from = WPCRecord::new_record($record_from);
        if ($record_to && ! is_object($record_to))
            $record_to = WPCRecord::new_record($record_to);

        $data = array(
            'id'          => $this->id,
            "record_from" => $record_from,
            "record_to"   => $record_to,
        );

        parent::__construct($data, $meta);
    }

    function set($key, $val) {
        if ($key === 'record_to')
            return $this->to($val);
        if ($key === 'record_from')
            return $this->from($val);

        return parent::set($key, $val);
    }

    function to($record) {
        if (! is_object($record))
            $record = WPCRecord::new_record($record, null, null, $this->to_type);
        $this->data['record_to'] = $record;

        return $this;
    }
    function from($record) {
        if (! is_object($record))
            $record = WPCRecord::new_record($record, null, null, $this->from_type);
        $this->data['record_from'] = $record;

        return $this;
    }

    function commit() {
        global $wpc_relationships;
        $relation = $wpc_relationships[$this->typeslug];

        $this->data = $this->data_to_update + $this->data;

        if ($this->data['record_to']->id === "" || $this->data['record_from'] === "") {
            ButterLog::error('From- or To-Record not given or not in database. You might need to commit them.');
            return;
        }


        $arg = array(
            'from_id'           => $this->data['record_from']->id,
            'to_id'             => $this->data['record_to']->id,
            'rel_id'            => $this->typeslug,
            'relation_metadata' => $this->meta_to_update,
            'id'                => $this->id
        );

        if ($this->id === null)
            $res = $relation->add_relation($arg);
        else
            $res = $relation->update_relation($arg);

        // invalidate metadata
        $this->meta = null;

        return $this;
    }

    protected function load_data() {
        global $wpdb;
        global $wpc_relationships;

        if ($this->id === null)
            return;

        // this should inner join on the two tables and get the record's data
        $table = $wpc_relationships[$this->typeslug]->table;
        $stmt = $wpdb->prepare("SELECT r.*, a.*, b.* FROM $table AS r
            WHERE r.id = %d", $this->id);
        $row = $wpdb->get_row($stmt, ARRAY_A);

        $this->data['record_from'] = WPCRecord::new_record($row[$this->post_from_id], null, null, $this->from_type);
        $this->data['record_to'] = WPCRecord::new_record($row[$this->post_to_id], null, null, $this->to_type);
    }
    protected function load_meta() {
        if ($this->data === null || ! empty($this->meta_keys))
            // load_data will also load meta-data
            return $this->load_data();
    }

    function delete() {
        global $wpdb;

        $table = $wpc_relationships[$this->typeslug]->table;
        $stmt = $wpdb->prepare("DELETE FROM $table WHERE id = %d", $this->id);
        $wpdb->query($stmt);
    }

    /**
     * returns a new object of the right type.
     */
    static function new_relation($id, $record_from, $record_to, $typeslug, $meta=null) {
        ButterLog::debug("WPCRelation::new_relation($id, $record_from, $record_to, $typeslug, $meta)");

        $classname = $typeslug."Relation";
        self::make_specific_class($classname, $typeslug);

        return new $classname($id, $record_from, $record_to, $meta);
    }
}
?>
