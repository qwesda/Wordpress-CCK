<?php

abstract class WPCCollection {

    /**
     * the name of the main table
     */
    protected $table;

    /**
     * the primary key of the main table
     */
    protected $table_pk;

    /**
     * the columns to filter by in the main table
     */
    protected $table_cols;

    /**
     * the meta table-name
     */
    protected $meta_table;

    /**
     * the meta table's foreign key to the main table
     */
    protected $meta_fk;



    /**
     * the order by key
     */
    protected $order_by = array();

    /**
     * the offset number
     */
    protected $offset = null;

    /**
     * the limit count
     */
    protected $limit = null;

    /**
     * the where clauses
     */
    protected $where = array();

    /**
     * the additional join clauses
     */
    protected $join = array();



    /**
     * Prepares the object to iterate over the results. Resets the iteration pointer.
     */
    function iterate () {
        // do only get results the first time it is called
        if (! isset($this->iterate_results))
            $this->iterate_results = $this->results();
		
        $this->iterate_pointer = 0;
		
        return $this;
    }

    /**
     * iterate over the fetched results (in iterate())
     * return false, if there are no more results.
     */
    function next () {
        // although against API, support next() w/o previous iterate().
        if (!isset ($this->iterate_results))
            $this->iterate();

        if (count($this->iterate_results) <= $this->iterate_pointer)
            return false;

        return $this->iterate_results[$this->iterate_pointer++];
    }

    /**
     * gets the first result trough iterate() and next().
     */
    function first_record () {
        $this->iterate();
		
		$ret = $this->next();
		
        return ( !empty($ret) ? $ret->other_record : NULL );
    }

    /**
     * order by column $col.
     * direction is either "ASC" or "DESC" for ascending or descending order. defaults to ASC.
     *
     * Note, right now, only wp_post columns are supported.
     */
    function order_by($col, $dir = "ASC"){
        if (! in_array($dir, array("ASC", "DESC"))) {
            // XXX: _error would be more appropriate
            _log("$dir is neither ASC nor DESC.");
            return $this;
        }

        if (! in_array($col, $this->table_cols)){
            // XXX: _error would be more appropriate
            _log("$col is no supported column to order by.");
            return $this;
        }

        $order_by_str = "t.$col $dir";
        if (in_array($order_by_str, $this->order_by))
            // we already order by this col
            return $this;

        $new = clone($this);
        $new->order_by[] = $order_by_str;
        return $new;
    }

    /**
     * limits the number of records to fetch
     */
    function limit($count) {
        $new = clone($this);
        $new->limit = $count;
        return $new;
    }

    function offset($offset) {
        $new = clone($this);
        $new->offset = $offset;
        return $new;
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
     * $key is one of the cols in $table_cols or a meta_key in meta_table
     * $value is the intended value (or array of values for IN, BETWEEN and its variants).
     * $op is the operator (one of "=", "<=>", "!=", "<", ">", "<=", ">=", "LIKE", "NOT LIKE", "IN", "NOT IN", "BETWEEN", "NOT BETWEEN", "IS", "IS NOT"). Default operator is "=".
     *
     * This can be overwritten to e.g. normalize keys.
     *
     * Note: for negative queries on the meta table, it will not list records w/o the key set.
     */
    function add_filter_($key, $val, $op="=") {
        global $wpdb;

        if (in_array($key, $this->table_cols))
            $this->where[] = $this->where_clause("t.$key", $val, $op);
        else {
            $join_ix = count($this->join);
            $alias = "wpcj$join_ix";
            $this->join[$join_ix] = "INNER JOIN $this->meta_table AS $alias
                ON $alias.$this->meta_fk = t.$this->table_pk";

            $filter = $wpdb->prepare("$alias.meta_key = %s AND ", $key);
            $filter.= $this->where_clause("$alias.meta_value", $val, $op);
            $this->where[] = $filter;
        }

        // invalidate iterate_results
        unset($this->iterate_results);
    }

    /**
     * returns all filtered records as array.
     */
    function results() {
        global $wpdb;

        $sql = "SELECT DISTINCT t.*, meta.meta_key, meta.meta_value FROM $this->table AS t
            LEFT JOIN $this->meta_table AS meta ON meta.$this->meta_fk = t.$this->table_pk\n";

        $sql.= join("\n", $this->join);

        if (count($this->where))
            $sql.= "\nWHERE ( ".join(" )\n  AND ( ", $this->where)." )\n";

        // add default ASC order by table's pk
        $order_by = $this->order_by;
        if (! (in_array("t.$this->table_pk ASC", $order_by) || in_array("t.$this->table_pk DESC", $order_by)))
            $order_by[] = "t.$this->table_pk ASC";
        $sql.= "ORDER BY ".join(", ", $order_by)."\n";

        if (isset($this->limit)) {
            $sql.= "LIMIT $this->limit ";
            if (isset($this->offset))
                $sql.= "OFFSET $this->offset";
        }
        $sql.= ";";

        _log("SQL query about to execute:\n$sql");

        $res = array();

        $dbres = mysql_query($sql);
        if (! $dbres) {
            // XXX: this should display an error (_error function needed?)
            _log("Could not execute the following SQL.\n$sql\nmysql_error:\n".mysql_error());
            return array();
        }

        $r = null; $meta = array();
        $cur_id = -1;
        // aggregate
        while ($row = mysql_fetch_assoc($dbres)) {
            if ($cur_id != $row[$this->table_pk]) {
                // add the now complete record to the array to return later
                // (do not do this the first time)
                if ($cur_id != -1) {
                    $res[] = array(
                        "$this->table_pk"=> $cur_id,
                        "$this->table"   => $r,
                        "meta"           => $meta
                    );
                    $meta = array();
                }

                $cur_id = $row[$this->table_pk];

                // copy the row and remove meta-fields
                $r = $row;
                foreach (array("meta_key", "meta_value") as $metakey)
                    unset($r[$metakey]);
            }
            if (! empty($row["meta_value"]))
              array_push($meta, array($row["meta_key"] => $row["meta_value"]));
        }
        // add the last completed record
        if ($cur_id != -1)
            $res[] = array(
                "$this->table_pk"=> $cur_id,
                "$this->table"   => $r,
                "meta"           => $meta
            );

        return $res;
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
      #  _log($filter);
        return $filter;
    }

}

?>
