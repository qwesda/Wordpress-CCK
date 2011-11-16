<?php

/**
 * relations records
 */
class GenericRelationRecords extends RecordList{

    protected $table = "wp_wpc_relations";
    protected $table_pk = "relation_id";
    protected $table_cols = array(
        "post_to_id",
        "post_from_id",
        "relationship_id"
    );
    protected $meta_table = "wp_wpc_relations_meta";
    protected $meta_fk = "relation_id";

    /**
     * the relationship's name in the db
     */
    protected $db_relationslug = '';

    /**
     * is set to false if the relationship is stored in the same order in the database.
     */
    protected $db_is_reverse = false;

    /**
     * returns an instance of (a subclass of) GenericRelationsRecords for both types.
     * if $id is set to a valid id of type $type, prefilter to get only connected relations.
     */
    static function relations_for_types($type, $othertype, $id=null) {
        global $wpc_relationships;

        $db_relationslug = $type."_".$othertype;
        if (! isset($wpc_relationships[$db_relationslug]))
            $db_relationslug = $othertype."_".$type;
        if (! isset($wpc_relationships[$db_relationslug])) {
            // XXX: there should be an _error here!
            _log("The relationship between $type and $othertype does not exist in the database.");
            return null;
        }

        $classname = ucfirst($type).ucfirst($othertype)."RelationRecords";
        if (! class_exists($classname)){
            $classdef = "class $classname extends ".__CLASS__." {
              protected \$db_relationslug = '$db_relationslug';
            }";
            eval ($classdef);
        }

        $relations = new $classname();

        if ($id !== null)
            $relations = $relations->id_is($id);

        return $relations;
    }

    function __construct() {
        if (isset($db_relationslug))
            $this->add_filter_('relationship_id', $this->db_relationslug);
    }

    /**
     * Prepares the object to iterate over the results. Resets the iteration pointer.
     *
     * For explanation of $as, see results().
     */
    function iterate ($as='OBJECT') {
        // do only get results the first time it is called
        if (! isset($this->iterate_results))
            $this->iterate_results = $this->results($as);
        $this->iterate_pointer = 0;
        return $this;
    }

    /**
     * returns all filtered relations in the following form if $as is 'ARRAY_A'.
     *
     * $relations = array(
     *    array(
     *        "relation_id"     => 1,
     *        "record"          => GenericRecord($id),
     *        "other_record"    => GenericRecord($other_id),
     *        "relationship_id" => "person_institution",
     *        "meta"            => array("key1"=>"value1", [...])
     *     ),
     *     [...]
     * );
     *
     * It will convert to an object, if $as is 'OBJECT' (default).
     */
    function results($as='OBJECT') {
        $res = array_map(array($this, "row_to_object"), parent::results());
        if ($as == 'OBJECT')
            foreach ($res as &$r) {
                $r = (object) $r;
                $r->meta = (object) $r->meta;
            }
        return $res;
    }
    protected function row_to_object ($record) {
        $row = $record[$this->table];

        $relationship_id = $this->db_relationslug !== '' ? $this->db_relationslug : $row["relationship_id"];
        list($one_type, $another_type) = explode('_', $relationship_id);

        $new = array(
            "relation_id"     => $row["relation_id"],
            "record"          => GenericRecord::new_type($this->db_is_reverse ? $row["post_to_id"] : $row["post_from_id"], $one_type),
            "other_record"    => GenericRecord::new_type($this->db_is_reverse ? $row["post_from_id"] : $row["post_to_id"], $another_type),
            // the following looks odd, but is correct:
            // if db_relationslug is ', the relation cannot be reverse.
            "relationship_id" => $relationship_id,
            "meta"            => $record["meta"]
        );
        return $new;
    }

    /**
     * convenience method for filter. filters for the id.
     * returns a new instance.
     */
    function id_is($id) {
        return $this->filter("id", $id);
    }

    /**
     * convenience method for filter. filters for the other id.
     * returns a new instance.
     */
    function other_id_is($id) {
        return $this->filter("other_id", $id);
    }

    /**
     * this performs some magic to (re)order from and to to the expected order.
     */
    function add_filter_($key, $val, $op="=") {
        if ($key == "id")
            $key = $this->db_is_reverse ? "post_to_id" : "post_from_id";
        else if ($key == "other_id")
            $key = $this->db_is_reverse ? "post_from_id" : "post_to_id";

        parent::add_filter_($key, $val, $op);
    }

}
?>
