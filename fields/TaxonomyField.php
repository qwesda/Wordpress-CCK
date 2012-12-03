<?php

/**
 *
 */

class TaxonomyField extends GenericField {
    function __construct ($parent, $params) {
        if ( !empty($params['taxonomy']) )   $this->taxonomy = $params['taxonomy'];

        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";

        $terms = get_terms( $this->taxonomy, array(
            'orderby'        => 'name',
            'hide_empty'     => 0,
            'hierarchical'   => true
        ) );

        echo "<div class='wpc_field_taxonomy_selector'>";

        $selected_taxonomies = !empty($value) ? explode(",", $value) : array();

        foreach ($terms as $term) {
            $cb_id = $this->id."_".$term->term_id; ?>
            <input type="checkbox" value="<?php echo $term->term_id ?>" id="<?php echo $cb_id ?>" <?php if (in_array($term->term_id, $selected_taxonomies)) echo "checked" ?>> <label for="<?php echo $cb_id ?>"><?php echo "$term->name ($term->count)" ?></label><br>

        <?php }

        echo "<input type='hidden' name='wpc_$this->id' class='wpc_field_taxonomy_selector_value' value='".implode(",", $selected_taxonomies)."' id='wpc_field_$this->id'>";
        echo "</div>";
    }
}

?>
