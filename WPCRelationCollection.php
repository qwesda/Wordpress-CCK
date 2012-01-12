<?php
require_once('WPCCollection.php');
require_once('WPCRelation.php');

/**
 * relations records
 */
class WPCRelationCollection extends WPCCollection {

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
     * returns an instance of (a subclass of) WPCRelationCollection for both types.
     * if $id is set to a valid id of type $type, prefilter to get only connected relations.
     */
    static function relations_for_types($type, $othertype, $id=null) {
        global $wpc_relationships;

        $db_relationslug = $type."_".$othertype;
        $db_is_reverse = false;

        if (! isset($wpc_relationships[$db_relationslug])) {
            $db_relationslug = $othertype."_".$type;
            $db_is_reverse = true;

            if (! isset($wpc_relationships[$db_relationslug])) {
                // XXX: there should be an _error here!
                _log("The relationship between $type and $othertype does not exist in the database.");
                return null;
            }
        }

        $classname = ucfirst($type).ucfirst($othertype)."RelationRecords";
        if (! class_exists($classname)){
            $classdef = "class $classname extends ".__CLASS__." {
              protected \$db_relationslug = '$db_relationslug';
              protected \$db_is_reverse = $db_is_reverse;
            }";
            eval ($classdef);
        }

        $relations = new $classname();

        if ($id !== null)
            $relations = $relations->id_is($id);

        return $relations;
    }

    function __construct() {
        if (isset($this->db_relationslug))
            $this->add_filter_('relationship_id', $this->db_relationslug);
    }

    /**
     * returns all filtered relations as array of WPCRelation objects.
     */
    function results() {
        $res = array_map(array($this, "row_to_relation"), parent::results());
        return $res;
    }
    function row_to_relation ($record) {
        $row = $record[$this->table];

        $relationship_id = $row["relationship_id"];
        if ($this->db_is_reverse) {
            list($type, $other_type) = explode($relationship_id);
            $relationship_id = $othertype."_$type";
        }

        return WPCRelation::new_relation(
            $row["relation_id"],
            $this->db_is_reverse ? $row["post_to_id"] : $row["post_from_id"],
            $this->db_is_reverse ? $row["post_from_id"] : $row["post_to_id"],
            $record["meta"],
            $relationship_id
        );
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
