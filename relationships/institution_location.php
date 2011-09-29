<?php 

/**
 * 
 */

class institution_location extends __GenericRelationship {
    function __construct () {
        $this->label            = "Institution to Location";

        $this->post_type_from_id= "institution";
        $this->post_type_to_id  = "location";

        $this->fields           = array(
            'type'                      => (object)array('type' => 'select',    'default' => '',        'label' => 'Type'               , 'hint' => '',    
                                        'options' => array('Main location', 'Additional location')),
        );

        parent::__construct ();
    }
}

 ?>