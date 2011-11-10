<?php

/**
 * relations records
 */
class GenericRelationRecords {

    /**
     * the relationship's name in the db
     */
    protected $db_relationslug = '';

    /**
     * is set to false if the relationship is stored in the same order in the database.
     */
    protected $db_is_reverse = false;

    /**
     * the where clauses
     */
    protected $where = array();

    /**
     * the additional join clauses
     */
    protected $join = array();

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
     * It will convert to an object, if $as is 'OBJECT'.
     */
    function results($as='OBJECT') {
        global $wpdb;

        $sql = "SELECT DISTINCT wpcr.*, wpcm.meta_id, wpcm.meta_key, wpcm.meta_value FROM wp_wpc_relations AS wpcr
            LEFT JOIN wp_wpc_relations_meta AS wpcm ON wpcm.relation_id = wpcr.relation_id\n";

        $sql.= join("\n", $this->join);

        $this->where = array_filter($this->where);
        if (count($this->where))
            $sql.= "WHERE ( ".join(" )\n  AND ( ", $this->where)." )\n";

        $sql.= "ORDER BY wpcr.relation_id;";

        _log("SQL query about to execute:\n$sql");

        $res = array();

        $dbres = mysql_query($sql);
        if (! $dbres) {
            // XXX: this should display an error (_error function needed?)
            _log("Could not execute the following SQL.\n$sql\nmysql_error:\n".mysql_error());
            return array();
        }

        $i = -1;
        $prev_relation_id = -1;
        // aggregate
        while ($row = mysql_fetch_assoc($dbres)) {
            if ($prev_relation_id != $row["relation_id"]) {
                $i++;
                $prev_relation_id = $row["relation_id"];

                $relationship_id = $this->db_relationslug !== '' ? $this->db_relationslug : $row["relationship_id"];
                list($one_type, $another_type) = explode('_', $relationship_id);
                $one_type    .= "Record";
                $another_type.= "Record";

                $res[$i] = array(
                    "relation_id"     => $row["relation_id"],
                    "record"          => new $one_type($this->db_is_reverse ? $row["post_to_id"] : $row["post_from_id"]),
                    "other_record"    => new $another_type($this->db_is_reverse ? $row["post_from_id"] : $row["post_to_id"]),
                    // the following looks odd, but is correct:
                    // if db_relationslug is '', the relation cannot be reverse.
                    "relationship_id" => $relationship_id,
                    "meta"            => array()
                );
            }
            if (! empty($row["meta_value"]))
              array_push($res[$i]["meta"], array($row["meta_key"] => $row["meta_value"]));
        }
        if ($as === 'OBJECT')
            foreach ($res as &$r) {
                $r = (object) $r;
                $r->meta = (object) $r->meta;
            }
        return $res;
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
     * returns a new instance, filtered by the filter. see add_filter_ for documentation.
     */
    function filter($key, $val, $op="=") {
        $new = clone($this);
        $new->add_filter_($key, $val, $op);
        return $new;
    }

    /**
     * adds a filter inplace.
     * $key is either "id" or "other_id" for the post ids in wp_wpc_relations or the meta_key in wp_wpc_relations_meta.
     * $value is the intended value (or array of values for IN, BETWEEN and its variants).
     * $op is the operator (one of "=", "<=>", "!=", "<", ">", "<=", ">=", "LIKE", "NOT LIKE", "IN", "NOT IN", "BETWEEN", "NOT BETWEEN", "IS", "IS NOT"). Default operator is "=".
     */
    function add_filter_($key, $val, $op="=") {
        global $wpdb;

        switch ($key) {
        case "id":
            $key = $this->db_is_reverse ? "post_to_id" : "post_from_id";
            $this->where[] = $this->where_clause("wpcr.$key", $val, $op);
            break;
        case "other_id":
            $key = $this->db_is_reverse ? "post_from_id" : "post_to_id";
            $this->where[] = $this->where_clause("wpcr.$key", $val, $op);
            break;
        default:
            $join_ix = count($this->join);
            $alias = "wpcm$join_ix";
            $this->join[$join_ix] = "INNER JOIN wp_wpc_relations_meta AS $alias ON $alias.relation_id = wpcr.relation_id";

            $filter = $wpdb->prepare("$alias.meta_key = %s AND ", $key);
            $filter.= $this->where_clause("$alias.meta_value", $val, $op);
            $this->where[] = $filter;

            _log ("meta filter '$filter' added.");
        }

        // invalidate iterate_results
        unset($this->iterate_results);
    }

    /**
     * returns a where clause (without "WHERE")
     */
    protected function where_clause($key, $val, $op) {
        global $wpdb;

        $filter = "$key $op ";

        switch ($op) {
        case "IN":
        case "NOT IN":
            if (!is_array($val)) {
                // XXX: _error would be more appropiate
                _log("$op needs an array. '".print_r($val)."' given");
                // XXX: _error would be more appropiate _log("$op needs an array. '".print_r($val)."' given");
                return;
            }
            $c = count($val);
            $filter = $wpdb->prepare($filter."( ".str_repeat("%s, ", $c-1)."%s )", $val);
            break;
        case "BETWEEN":
        case "NOT BETWEEN":
            if (!is_array($val) || $c=count($val) != 2) {
                // XXX: _error would be more appropiate
                _log("$op needs an array of length 2. '".print_r($val)."' given");
                return;
            }
            $filter = $wpdb->prepare($filter."%s AND %s", $val);
            break;
        case "IS":
        case "IS NOT":
            $allowed_values = array("FALSE", "TRUE", "UNKNOWN", "NULL");
            if (! in_array(strtoupper($val), $allowed_values)) {
                _log("$op only supports the values ".join(', ', $allowed_values)."\n$val given");
                return;
            }
            $filter.= $val;
            break;
        case "LIKE":
        case "NOT LIKE":
            $filter.= "'%".like_escape($val)."%'";
            break;
        default:
            if (! in_array($op, array("=", "!=", "<=>","<","<=",">","=>"))) {
                // XXX: _error would be more appropiate
                _log("operator is not valid: $op");
                return;
            }
            $filter = $wpdb->prepare($filter."%s", $val);
        }
        return $filter;
    }
}
?>
