<?php

/**
 *
 */
class TextDateField extends GenericRegexValidatingField {
    protected $replacements = array(
        array(
            '/^(\d|[012]\d|3[01]) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|[1-9]|1[012]) (\d{4})$/',
            'function(str, day, month, year) { return mysqldate(new Date(str)); }',
            'false'),
        array(
            '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|[1-9]|1[012]) (\d|[012]\d|3[01]), (\d{4})$/',
            'function(str, month, day, year) { return mysqldate(new Date(str)); }',
            'false')
        );

    protected function display_value($value) {
        return mysql2date(__('M j, Y'), $value);
    }
}
?>