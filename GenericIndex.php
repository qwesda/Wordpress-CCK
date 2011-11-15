<?php

abstract class GenericIndex {
    protected $rewrite_rules    = array();
    protected $query_vars       = array();

    function __construct () {
        add_filter('rewrite_rules_array', array(&$this, 'rewrite_rules_array') );
        add_filter('query_vars', array(&$this, 'query_vars') );
    }



    function rewrite_rules_array ($rules) {
        return $this->rewrite_rules + $rules;
    }

    function query_vars ($vars) {
        return $this->query_vars + $vars;
    }

    function echo_index () {

    }
}

?>