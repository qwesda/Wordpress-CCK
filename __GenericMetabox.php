<?php 

class GenericMetabox {
    public $metabox_id      = NULL;
    public $content_type    = NULL;
    public $label           = "";

    public $context         = "advanced";
    public $priority        = "high";
    
    function __construct () {

//  SET DEFAULTS
        if(empty($this->metabox_id))            $this->metabox_id       = strtolower(get_class($this));
        if(empty($this->label))                 $this->label            = strtolower(str_replace("_", " ", $this->metabox_id));

    }

    function register_metabox() {
        
    }

    function echo_metabox () {
        $this->content_type->first_metabox();
    }

    
}

?>