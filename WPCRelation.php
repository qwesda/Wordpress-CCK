<?php

/**
 * The base class for records of a content type.
 *
 * Does not lazy-load data.
 */
abstract class WPCRelation extends WPCData {

    /**
     * constructor for a Relation
     *
     *
     */
    protected function __construct($id, $record_from, $record_to, $meta=null) {
        global $wpc_relationships;

        if ($id === null || $record_from === null || $record_to === null) {
            ButterLog::warn(get_class($this).": Id, record or other_record not given!");
            throw new Exception("Cannot construct ".get_class($this).". Missing Parameter.");
        }

        $this->id = $id;

        // if only the record's or other record's id is given, construct a new record object
        if (! is_object($record_from)) {
            $from_type = $wpc_relationships[$this->typeslug]->post_type_from_id;
            $record_from = WPCRecord::new_record($record_from, null, null, $from_type);
        }
        if (! is_object($record_to)) {
            $to_type = $wpc_relationships[$this->typeslug]->post_type_to_id;
            $record_to = WPCRecord::new_record($record_to, null, null, $to_type);
        }

        $data = array(
            "record_from"     => $record_from,
            "record_to"       => $record_to,
            "record_type"     => $from_type,
            "record_to_type"  => $to_type,
            "relationship_id" => $this->typeslug
        );

        $this->data_keys = array_keys($data);
        $this->meta_keys = array_keys($wpc_relationships[$this->typeslug]);

        ButterLog::debug($data);

        parent::__construct($data, $meta);
    }

    function commit() {
        global $wpc_relationships;
        $relation = $wpc_relationships[$this->typeslug];

        $arg = array(
            'from_id'           => $this->data_to_update['record_from'],
            'to_id'             => $this->data_to_update['record_to'],
            'rel_id'            => $this->typeslug,
            'relation_metadata' => $this->meta_to_update
        );

        if ($this->id === null)
            $res = $relation->add_relation($arg);
        else
            $res = $relation->update_relation($arg);
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
