<?php

/**
 *
 */

class GeneratedValue extends FormattedString {
    function __construct ($parent, $params, $callback) {
        parent::__construct ($parent, $params, $callback);
    }

    function may_write () {
        return false;
    }
}

?>
