<?php
require_once('WPCCollection.php');
require_once('WPCRelation.php');

/**
 * relations records
 */
class WPCRelationCollection extends WPCCollection {
    protected $table_pk = "id";
    protected $table_cols = array('id', 'post_from_id', 'post_to_id');

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
        $table = $wpc_relationships[$typeslug]->table;

        $classname = ($reverse ? "Reverse" : "").str_replace(" ", "", ucwords(str_replace("_", " ", $typeslug)))."RelationRecords";
        if (! class_exists($classname)){
            $classdef = "class $classname extends ".__CLASS__." {
              protected \$typeslug = '$typeslug';
              protected \$db_is_reverse = $db_reverse;
              protected \$table = '$table';
            }";
            eval ($classdef);
        }

        $relations = new $classname();

        if ( !empty($id) ) {
            $relations = $relations->id_is($id);
        }

        return $relations;
    }

    function __construct() {
        global $wpc_relationships;

        // add metafields
        $this->table_cols = array_unique(array_merge($this->table_cols,
            array_keys($wpc_relationships[$this->typeslug]->fields)));
    }

    /**
     * returns all filtered relations as array of WPCRelation objects.
     */
    function results() {
        return array_map(array($this, "row_to_relation"), parent::results());
    }


    function next () {
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

        $row = $record['t'];
        return WPCRelation::new_relation(
            $record['id'],
            $row['post_from_id'],
            $row['post_to_id'],
            $this->typeslug,
            $record['m']
        )->write_ro($this->write_ro);
    }

    /**
     * convenience method for filter. filters for the id.
     * returns a new instance.
     */
    function id_is($id) {
        return $this->filter('post_from_id', $id);
    }

    /**
     * convenience method for filter. filters for the other id.
     * returns a new instance.
     */
    function other_id_is($id) {
        return $this->filter("post_to_id", $id);
    }

    /**
     * this performs some magic to (re)order from and to to the expected order.
     */
    function add_filter_($key, $val, $op="=") {
        if ($key == "post_from_id")
            $key = $this->db_is_reverse ? "post_to_id" : "post_from_id";
        else if ($key == "post_to_id")
            $key = $this->db_is_reverse ? "post_from_id" : "post_to_id";

        parent::add_filter_($key, $val, $op);
    }

}
?>
