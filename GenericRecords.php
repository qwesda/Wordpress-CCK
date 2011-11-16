<?php

/**
 * records
 */
class GenericRecords {

    /**
     * the where clauses
     */
    protected $where = array();

    /**
     * the additional join clauses
     */
    protected $join = array();

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
        global $wpdb;

        $sql = "SELECT DISTINCT posts.*, meta.* FROM $wpdb->posts AS posts
            LEFT JOIN $wpdb->postmeta AS meta ON meta.post_id = posts.ID\n";
        $sql.= join("\n", $this->join);

        $this->where = array_filter($this->where);
        if (count($this->where))
            $sql.= "\nWHERE ( ".join(" )\n  AND ( ", $this->where)." )\n";

        $sql.= "ORDER BY posts.ID;";

        _log("SQL query about to execute:\n$sql");

        $res = array();

        $dbres = mysql_query($sql);
        if (! $dbres) {
            // XXX: this should display an error (_error function needed?)
            _log("Could not execute the following SQL.\n$sql\nmysql_error:\n".mysql_error());
            return array();
        }

        $p = null; $meta = array();
        $cur_id = -1;
        // aggregate
        while ($row = mysql_fetch_assoc($dbres)) {
            if ($cur_id != $row["ID"]) {
                // add the now complete record to the array to return later
                // (do not do this the first time)
                if ($cur_id != -1) {
                    $res[] = GenericRecord::new_type($cur_id, null, $p, $meta);
                    $meta = array();
                }

                $cur_id = $row["ID"];

                // copy the row and remove meta-fields
                $p = $row;
                foreach (array("meta_id", "post_id", "meta_key", "meta_value") as $metakey)
                    unset($p[$metakey]);
            }
            if (! empty($row["meta_value"]))
              array_push($meta, array($row["meta_key"] => $row["meta_value"]));
        }
        // add the last completed record
        if ($cur_id != -1) {
            $res[] = GenericRecord::new_type($cur_id, null, $p, $meta);
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
     * $key is one of wp_posts cols or a meta_key in wp_postmeta
     * $value is the intended value (or array of values for IN, BETWEEN and its variants).
     * $op is the operator (one of "=", "<=>", "!=", "<", ">", "<=", ">=", "LIKE", "NOT LIKE", "IN", "NOT IN", "BETWEEN", "NOT BETWEEN", "IS", "IS NOT"). Default operator is "=".
     *
     * Note: For negative queries, it does not list relations without this key.
     */
    function add_filter_($key, $val, $op="=") {
        global $wpdb;

        $key = strtolower($key);

        if (in_array($key, array('id', 'post_author', 'post_date',
            'post_date_gmt', 'post_content', 'post_content_filtered',
            'post_title', 'post_excerpt', 'post_status', 'post_type',
            'comment_count', 'comment_status', 'ping_status', 'post_password',
            'post_name', 'to_ping', 'pinged', 'post_modified',
            'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type',
            'guid')))
                $this->where[] = $this->where_clause("posts.$key", $val, $op);
        else {
            $join_ix = count($this->join);
            $alias = "wpm$join_ix";
            $this->join[$join_ix] = "INNER JOIN $wpdb->postmeta AS $alias ON $alias.post_id = posts.ID";

            $filter = $wpdb->prepare("$alias.meta_key = %s AND ", $key);
            $filter.= $this->where_clause("$alias.meta_value", $val, $op);
            $this->where[] = $filter;
        }

        // invalidate iterate_results
        unset($this->iterate_results);
    }

    /**
     * returns a where clause (without "WHERE")
     *
     * XXX: this is a copy of GenericRelationRecords::where_clause. this should be one function!
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
