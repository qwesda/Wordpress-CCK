<?php

/**
 *
 */

class GeneratedValue extends FormattedString {
    /*  das muss jetzt nur noch >entwerder< in die meta-db geschrieben werden
        oder in die wp_post tabelle falls es post_title überschriebt ... bei den restlichen feldern macht das glaube ich weniger sinn */

    function __construct ($parent, $params, $callback) {
        parent::__construct ($parent, $params, $callback);

        $parent->generated_values[$this->id] = $this;
    }

    function may_write () {
        return false;
    }

    function value($post_id) {
        $record = the_record($post_id);
        return $record->__get($this->id);
    }
}

?>
