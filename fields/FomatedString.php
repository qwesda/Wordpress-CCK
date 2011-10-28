<?php 

/**
 * 
 */

class FormatedString extends GenericField {
    function __construct ($parent, $params, $callback) {
        parent::__construct ($parent, $params); 

        $filter_id = "wpc_format_".$this->parent->id."_".$this->id;

        add_filter($filter_id, $callback);
    }


    function echo_field_core ($post_data = array ()) {  ?>
        <label class="wpc_input_label"><?php if ( !empty($post_data) ) echo $post_data[$this->id] ?></label>
    <?php }
}

?>