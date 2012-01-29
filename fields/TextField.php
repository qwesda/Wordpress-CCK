<?php

/**
 *
 */

class TextField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }


    function echo_field_core ($post_data = array ()) {
     ?>
        <input type="text"
               id="wpc_field_<?php echo $this->id; ?>"
               class="wpc_input wpc_input_text <?php if ($this->localized) echo "wpc_localized_input";?>"
               name="<?php echo "wpc_$this->id"; ?>"
               value="<?php if ( !empty($post_data) ) echo $post_data[$this->id]; ?>"
               placeholder="<?php echo $this->hint; ?>" />
    <?php }
}

?>
