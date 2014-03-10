<?php

/**
 *
 */

class GeneratedValue extends FormattedString {
    function __construct ($parent, $params, $callback) {
        parent::__construct ($parent, $params, $callback);

        $parent->generated_values[$this->id] = $this;
    }

    function may_write ($post_id = NULL) {
        return true;
    }

    function value($post_id) {
        $record = the_record($post_id);

        return $record->__get($this->id);
    }

    function value_uncached($post_id) {
        $record = the_record($post_id);

        return $record->formatted_string($this->id, true);
    }
}

?>
