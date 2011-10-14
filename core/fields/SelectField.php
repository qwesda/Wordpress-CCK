<?php 

/**
 * 
 */

class SelectField extends __GenericField {
    public $options = array();

    function __construct ($parent, $params) {
        if ( !empty($params['options']) )   $this->options = $params['options'];
        
        parent::__construct ($parent, $params); 
    }


    function echo_field_core ($post_data = array ()) {  ?>
        <select id="<?php echo "wpc_$this->id" ?>" class="wpc_input wpc_input_select"   name="<?php echo "wpc_$this->id" ?>" width="100%">
            <option value=""></option>
            <?php foreach ($this->options as $value): ?>
                <option value="<?php echo $value ?>" <?php if ( !empty($post_data) ) echo $post_data[$this->id] == $value ? 'selected' : '' ?>><?php echo $value ?></option>
            <?php endforeach ?>
        </select>
    <?php }
}

?>