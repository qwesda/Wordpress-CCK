<?php

abstract class GenericIndex {
    protected $rewrite_rules    = array();
    protected $query_vars       = array();

    function __construct () {
        add_filter ('rewrite_rules_array',  array($this, 'rewrite_rules_array') );
        add_filter ('query_vars',           array($this, 'query_vars') );
        add_action ('template_redirect',    array($this, 'template_redirect') );
        add_action ('wp_loaded',            array($this, 'wp_loaded') );

        foreach ($this->rewrite_rules as $key => $value) {
            $this->rewrite_rules[$key] .= "&index=".get_class($this);
        }

    }

    function wp_loaded(){
        $rules = get_option( 'rewrite_rules' );

        foreach ($this->rewrite_rules as $key => $value) {
            if ( !isset($rules[$key]) || $rules[$key] != $value ) {
                global $wp_rewrite;

                $wp_rewrite->flush_rules();
            }
        }
    }

    function rewrite_rules_array ($rules) {
        $ret = $this->rewrite_rules + $rules;

        return $ret;
    }

    function query_vars ($vars) {
        if ( !in_array("index", $vars) ) {
            array_push($vars, "index");
        }

        $ret =  $this->query_vars + $vars;

        return $ret;
    }

    function template_redirect ($vars) {
        global $wp;
        global $wp_rewrite;

        if ( !empty($wp->query_vars["index"]) && $wp->query_vars["index"] == get_class($this) ) {
            $this->echo_index();

            die();
        }
    }

    function echo_index () {

    }
}

?>