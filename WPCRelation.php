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
    function __construct($id, $record_from, $record_to, $meta=null) {
        global $wpc_relationships;

        if ($id === null || $record_from === null || $record_to === null) {
            ButterLog::warn(get_class($this).": Id, record or other_record not given!");
            throw new Exception("Cannot construct ".get_class($this).". Missing Parameter.");
        }

        $this->id = $id;

        // if only the record's or other record's id is given, construct a new record object

        if (! is_object($record_from)) {
            $type           = $wpc_relationships[$this->typeslug]->post_type_from_id;
            $record_from    = WPCRecord::new_record($record_from, null, null, $type);
        }
        if (! is_object($record_to)) {
            $record_to_type = $wpc_relationships[$this->typeslug]->post_type_to_id;
            $record_to      = WPCRecord::new_record($record_to, null, null, $record_to_type);
        }

        $data = array(
            "record_from"     => $record_from,
            "record_to"       => $record_to,
            "record_type"     => $type,
            "record_to_type"  => $record_to_type,
            "relationship_id" => $this->typeslug
        );


        ButterLog::debug($data);

        parent::__construct($data, $meta);
    }

    function arrify ($val) {
        return array($val);
    }

    /**
     * returns a new object of the right type.
     */
    static function new_relation($id, $record_from, $record_to, $typeslug, $meta=null) {
        ButterLog::debug("WPCRelation::new_relation($id, $record_from, $record_to, $meta, $typeslug)");

        $classname = $typeslug."Relation";
        self::make_specific_class($classname, $typeslug);

        return new $classname($id, $record_from, $record_to, $meta);
    }
}
?>
