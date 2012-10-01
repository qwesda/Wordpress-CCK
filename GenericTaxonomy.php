<?php

global $wpc_taxonomies;
$wpc_taxonomies = array();

abstract class GenericTaxonomy {
    public $id                  = NULL;

    public $label               = "";
    public $slug                = "";
    public $singular_label      = "";
    public $hierarchical        = false;
    public $for_post_types      = array();

    function __construct () {
        global $wpc_taxonomies;

//  SET DEFAULTS
        if ( empty($this->id) )             	$this->id               = strtolower ( get_class_name($this) );

        if ( empty($this->label) )          	$this->label            = $this->id . "s";
        if ( empty($this->singular_label) ) 	$this->singular_label   = $this->id;
        if ( empty($this->slug) )           	$this->slug             = $this->id;

        if ( empty($this->for_post_types) )     $this->for_post_types  = array();


        if ( in_array($this->id, get_taxonomies()) ) {
            die ("wpc taxonomy \"$this->id\" is not unique");

            return ;
        } else {
            $wpc_taxonomies[$this->id] = $this;
        }

//  REGISTER TAXONOMY
		register_taxonomy (
			$this->id,
			$this->for_post_types,
			array(
				'label' => $this->label,
				'hierarchical' => $this->hierarchical,
				'rewrite' => array( 'slug' => $this->slug )
			)
		);
    }

}

?>
