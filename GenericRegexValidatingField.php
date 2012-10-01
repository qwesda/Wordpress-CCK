<?php
/**
 * Base class for fields, that validate/sanatize its values using multiple
 * possible regular expressions.
 *
 * It overwrites echo_field_core of GenericField and inserts magic to parse
 * the required value.
 */
abstract class GenericRegexValidatingField extends GenericField {
    /**
     * The matching expression to replacements data of the form
     *     array( array("/regex/", "replace_for_db", "replace_for_ui"),
     *         [...] ),
     * where:
     *  - regex is matched against the user inputted string,
     *  - replace_for_db is the replacement to be put into the db,
     *  - replace_for_ui is the replacement to be shown to the user; might
     *    include html tags.
     *
     * All expressions will be copied verbatim and executed by javascript.
     * The first member must be a regular expression, i.e. "/regex/" (which will
     * become /regex/ in js). The replacement expressions can be either regular
     * expressions (s.a.), callbacks (e.g.: "function (match) {...}") (see [1]
     * for reference) or something evaluating to false, which will simply use
     * the original string.
     *
     * Try to ensure, that replace_for_ui returns parseable (matching) strings.
     *
     * Watch out Guys, we're dealing with badass stuff over here! Be sure to
     * check in scratchpad first, if what you write is valid js and does, in
     * fact, work.
     *
     * Examples for matching regex:
     * /^(\d+)(st|nd|rd)? (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)( of)? (\d{4})$/
     *  matches 2nd Jan of 2011 and 31 May 2044 (but also 54 Jul 0001)
     *
     * [1] https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/String/replace#Specifying_a_function_as_a_parameter
     */
    protected $replacements = array();


    /**
     * this gets called to transform the value into the user-visible string.
     * should return the string to display.
     *
     * This should probably match the corresponding display function or pattern
     * in replacements.
     */
    abstract protected function display_value($value);


    /**
     * echos two spans and the hidden input field.
     * the one span, initially hidden, contains a textbox, the other one
     * contains the user visible string and edit button.
     */
    function echo_field_core ($post_data = array()) {
        echo "<script type='text/javascript'>\n";
        // insert wpc_regexval = [[/regex/, "replace_for_db", "replace_for_ui"]
        echo "if (typeof wpc_regexval === 'undefined') wpc_regexval = [];\n";
        echo "wpc_regexval['$this->id'] = [";
        $map = function ($replacement) {
            return "[{$replacement[0]}, {$replacement[1]}, {$replacement[2]}]";
        };
        echo join(",\n", array_map($map, $this->replacements));
        echo "];\n";
        echo "</script>";

        echo "<input type='hidden' id='wpc_$this->id' name='wpc_$this->id' ";
        if ($post_data[$this->id] !== '')
            echo "value='{$post_data[$this->id]}' ";
        echo "/>\n";

        echo "<span id='wpc_regexval_display_$this->id' class='wpc_regexval_display'>";
        //echo "<span class='wpc_regexval_display_val'>";
        if ($post_data[$this->id] !== '')
            echo $this->display_value($post_data[$this->id]);
		else {
			echo "set value";
		}
        ButterLog::debug($this->display_value($post_data[$this->id]));
        //echo "</span>";
        echo "</span>\n";

        echo "<span id='wpc_regexval_input_$this->id' class='wpc_regexval_input' style='display: none'>";
        echo "<input type='text'
            id='wpc_input_regexval_$this->id'
            class='wpc_input wpc_input_text wpc_input_regexval_input'
            name='_wpc_$this->id'
            value='{$post_data[$this->id]}' />";
        echo "<a class='wpc_val_apply' href='#'><img src='".esc_url(admin_url('images/yes.png'))."' alt='apply' /></a>";
        echo "<a class='wpc_val_cancel' href='#'><img src='".esc_url(admin_url('images/no.png'))."' alt='cancel' /></a>";
        echo "</span>\n";
    }
}
?>
