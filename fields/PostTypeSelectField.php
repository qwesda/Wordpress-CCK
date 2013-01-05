<?php

/**
 *
 */

class PostTypeSelectField extends GenericField {
    function __construct ($parent, $params) {
        if ( !empty($params['post_type']) )   $this->post_type = $params['post_type'];

        parent::__construct ($parent, $params);
    }

    function echo_field_core ($with_default_value = false) {
        $record = the_record();
        $value  = $record && $this->parent->id == $record->post_type && !$with_default_value ? $record->__get($this->id) : "";

        $args           = array( 'numberposts' => -1, 'order'=> 'ASC', 'orderby' => 'title', 'post_type' => $this->post_type );
        $parent_posts   = get_posts($args);
     ?>

         <select id='<?php echo "wpc_field_$this->id" ?>' class="wpc_input wpc_input_select"   name='<?php echo "wpc_$this->id" ?>' width="100%">
             <option value='' <?php if ( empty($value) ) echo 'selected'; ?>></option>
             <?php  foreach ($parent_posts as $parent_post): ?>
                 <option value="<?php echo $parent_post->ID; ?>" <?php
                     if ( $value == $parent_post->ID ) echo 'selected'; ?>><?php echo $parent_post->post_title ?></option>
             <?php endforeach  ?>
         </select>

           <label class="wpc_helptext" for="wpc_field_<?php echo $this->id; ?>" style="display:none"><?php echo $this->helptext; ?></label>
    <?php }
}

?>
