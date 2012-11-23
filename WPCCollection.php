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
     * the callback to sort by, if needed.
     * should be a usort()-like comparision function.
     */
    protected $sort_by_callback = false;

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


    protected $write_ro = null;

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
     * returns the count
     */
    function count () {
        // shortcut, if there are already fetched results
        if (isset($this->iterate_results))
            return count($this->iterate_results);

        $dbres = $this->sql_results('COUNT(*)');
        $row = mysql_fetch_array($dbres);
        return intval($row[0]);
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

        $ret = $this->iterate_results[$this->iterate_pointer++];

        return $ret;
    }

    /**
     * gets the first result trough iterate() and next().
     */
    function first_record () {
        $this->iterate();
        $ret = $this->next();

        return ( !empty($ret) ? $ret : NULL );
    }

    function last_record() {
        $this->iterate();

        if (empty($this->iterate_results))
            return NULL;

        return $this->iterate_results[count($this->iterate_results)-1];
    }

    function each($fun) {
        $this->iterate();

        while($rec = $this->next())
            call_user_func($fun, $rec);

        return $this;
    }

    /**
     * order by column or callback $c.
     * direction is either "ASC" or "DESC" for ascending or descending order. defaults to ASC.
     *
     * A callback is assumed to conform to usort().
     */
    function order_by($c, $dir = "ASC"){
        if (! in_array($dir, array("ASC", "DESC"))) {
            // XXX: _error would be more appropriate
            ButterLog::warn("$dir is neither ASC nor DESC. Ignoring Order By clause.");
            return $this;
        }

        $new = clone($this);

        // callback
        if (is_callable($c)) {
            if (! empty($this->order_by))
                ButterLog::warn('You cannot have both: sorting with SQL and sorting with callback. Callback sorting will win.');
            $this->sort_by_cb = $c;
        }
        else {
            if (!empty($this->sort_by_cb))
                ButterLog::warn('You cannot have both: sorting with SQL and sorting with callback. This Sorting will most likely have no effect.');

            // regular column
            if (in_array($c, $this->table_cols))
                $order_by_str = "t.$c $dir";

            // meta column
            else
                $order_by_str = "m.$c $dir";

            if (in_array($order_by_str, $this->order_by))
                // we already order by this col
                return $new;

            $new->order_by[] = $order_by_str;
        }
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
        if ($offset > 0) {
            $new = clone($this);
            $new->offset = $offset;
            return $new;
        }
        return $this;
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
        if ($key == 'id')
            $this->where[] = $this->where_clause("t.$this->table_pk", $val, $op);
        elseif (in_array($key, $this->table_cols))
            $this->where[] = $this->where_clause("t.$key", $val, $op);
        else {
            $this->where[] = $this->where_clause("m.$key", $val, $op);
        }

        // invalidate iterate_results
        unset($this->iterate_results);
    }

    /**
     * returns all filtered records as array.
     */
    function sql_results($selectstr="*") {
        $sql = "SELECT $selectstr FROM $this->table AS t\n";

        if (! empty($this->meta_table))
            $sql .= "LEFT JOIN $this->meta_table AS m
                ON m.$this->meta_fk = t.$this->table_pk\n";

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

        $res = array();

        $dbres = mysql_query($sql);
        if (! $dbres) {
            // XXX: this should display an error (_error function needed?)
            ButterLog::error("Could not execute the following SQL.\n$sql\nmysql_error:\n".mysql_error());
            return array();
        }
        return $dbres;
    }

    function results() {
        $dbres = $this->sql_results();
        $res = array();
        $table_cols = array_flip($this->table_cols);
        while ($row = mysql_fetch_assoc($dbres))
            $res[] = array(
                'id' => $row[$this->table_pk],
                't'  => array_intersect_key($row, $table_cols),
                'm'  => array_diff_key($row, $table_cols)
            );

        // sort with callback if given
        // this is suboptimal. usort uses quicksort. so if the db presorts,
        // it will hit quicksorts worst case performance O(n²).
        if (!empty($this->sort_with_cb))
            $res = usort($res, $this->sort_with_cb);

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
            if (!is_array($val) && !empty($val) ) {
                $val = explode(",", $val);
            }

            if (!is_array($val)) {
                ButterLog::warn("$op needs an array. '".print_r($val)."' given");
                return;
            }
            $c = count($val);
            $filter = $wpdb->prepare($filter."( ".str_repeat("%s, ", $c-1)."%s )", $val);
            break;
        case "BETWEEN":
        case "NOT BETWEEN":
            if (!is_array($val) || $c=count($val) != 2) {
                ButterLog::warn("$op needs an array of length 2. '".print_r($val)."' given");
                return;
            }
            $filter = $wpdb->prepare($filter."%s AND %s", $val);
            break;
        case "IS":
        case "IS NOT":
            $allowed_values = array("FALSE", "TRUE", "UNKNOWN", "NULL");
            if (! in_array(strtoupper($val), $allowed_values)) {
                ButterLog::warn("$op only supports the values ".join(', ', $allowed_values)."\n$val given");
                return;
            }
            $filter.= $val;
            break;
        case "LIKE":
        case "NOT LIKE":
            $filter.= "'%".like_escape($val)."%'";
            break;
        case "IS NULL":
        case 'IS NOT NULL':
            // $key $op is already enought. no need to specify the value
            break;
        default:
            if (! in_array($op, array("=", "!=", "<=>","<","<=",">",">="))) {
                ButterLog::warn("operator is not valid: $op");
                return;
            }
            $filter = $wpdb->prepare($filter."%s", $val);
        }
        return $filter;
    }


    function write_ro($write_ro) {
        $this->write_ro = $write_ro;
        return $this;
    }
}

?>
