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
    function __construct($id, $record, $other, $meta=null) {
        if ($id === null || $record === null || $other === null) {
            _log(get_class($this).": Id, record or other_record not given!");
            throw new Exception("Cannot construct ".get_class($this).". Missing Parameter.");
        }

        $this->id = $id;

        // if only the record's or other record's id is given, construct a new record object
        if (! is_object($record)) {
            list($type, $other_type) = explode('_', $this->typeslug);
            $record = WPCRecord::new_record($record, null, null, $type);
        }
        if (! is_object($other)) {
            list($type, $other_type) = explode('_', $this->typeslug);
            $other = WPCRecord::new_record($other, null, null, $other_type);
        }

        $data = array(
            "record"          => $record,
            "other_record"    => $other,
            "relationship_id" => $this->typeslug
        );
        parent::__construct($data, $meta);
    }

    function arrify ($val) {
        return array($val);
    }

    /**
     * returns a new object of the right type.
     */
    static function new_relation($id, $record, $other, $meta=null, $typeslug=null) {
        if ($typeslug === null) {
            if (! is_object($record))
                $record = WPCRecord::new_record($record);
            if (! is_object($other))
                $other = WPCRecord::new_record($other);
            $typeslug = $record->post_type."_$other->post_type";
        }

        list($type1, $type2) = explode("_", $typeslug);
        $classname = ucfirst($type1).ucfirst($type2)."Relation";
        self::make_specific_class($classname, $typeslug);

        return new $classname($id, $record, $other, $meta);
    }
}
?>
