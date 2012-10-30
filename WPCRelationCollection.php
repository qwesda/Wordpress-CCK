<?php
require_once('WPCCollection.php');
require_once('WPCRelation.php');

/**
 * relations records
 */
class WPCRelationCollection extends WPCCollection {
    protected $table        = "wp_wpc_relations";
    protected $table_pk     = "relation_id";
    protected $table_cols   = array(
        "post_to_id",
        "post_from_id",
        "relationship_id"
    );
    protected $meta_table   = "wp_wpc_relations_meta";
    protected $meta_fk      = "relation_id";

    /**
     * the relationship's name in the db
     */
    protected $typeslug = '';

    /**
     * is set to false if the relationship is stored in the same order in the database.
     */
    protected $db_is_reverse = false;

    /**
     * returns an instance of (a subclass of) WPCRelationCollection for both types.
     * if $id is set to a valid id of type $type, prefilter to get only connected relations.
     */
    static function relations_by_id($typeslug, $reverse, $id=null) {
        global $wpc_relationships;

        if (! isset($wpc_relationships[$typeslug])) {
            // XXX: there should be an _error here!
            ButterLog::warn("The relationship with id $typeslug does not exist in the database.");
            return null;
        }

        $db_reverse = $reverse ? "true" : "false";

        $classname = ($reverse ? "Reverse" : "").str_replace(" ", "", ucwords(str_replace("_", " ", $typeslug)))."RelationRecords";
        if (! class_exists($classname)){
            $classdef = "class $classname extends ".__CLASS__." {
              protected \$typeslug = '$typeslug';
              protected \$db_is_reverse = $db_reverse;
            }";
            eval ($classdef);
        }

        $relations = new $classname();

        ButterLog::debug("relations_by_id($typeslug, $reverse, $id=null)");

        if ( !empty($id) ) {
            $relations = $relations->id_is($id);
        }

        return $relations;
    }

    function __construct() {
        if (isset($this->typeslug))
            $this->add_filter_('relationship_id', $this->typeslug);
    }

    /**
     * returns all filtered relations as array of WPCRelation objects.
     */
    function results() {
        $res = array_map(array($this, "row_to_relation"), parent::results());

        return $res;
    }


    function next () {
        ButterLog::debug("WPCRelationCollection::next() - $this->typeslug - $this->db_is_reverse");

        $next_relation = parent::next();
        $ret = null;


        if (!empty($next_relation)) {

            if ($this->db_is_reverse)   $ret = $next_relation->record_from;
            else                        $ret = $next_relation->record_to;

            if(!empty($ret)) {
                if ($ret->post_status != "publish") {
                    return $this->next();
                }
            }
        }

        return $ret;
    }





    function row_to_relation ($record) {
        global $wpc_relationships;

        $row = $record[$this->table];

        $relationship_id = $row["relationship_id"];

        return WPCRelation::new_relation(
            $row["relation_id"],
            $row["post_from_id"],
            $row["post_to_id"],
            $relationship_id,
            $record["meta"]
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
