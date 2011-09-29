<?php 

/**
 * 
 */

class person_institution extends __GenericRelationship {
    function __construct () {
        $this->label            = "Person to Institution";

        $this->post_type_from_id= "person";
        $this->post_type_to_id  = "institution";

        $this->fields           = array(
            'type'                      => (object)array('type' => 'select',    'default' => '',        'label' => 'Type'               , 'hint' => '',    
                                        'options' => array('member', 'HiWi', 'WiMi')),
        );

        parent::__construct ();
    }
}

 ?>