<?php 

global $wpc_content_types;
$wpc_content_types = array();

class __GenericContentType {
    public $id                  = NULL;
    public $fields              = array();
    
    public $label               = "";
    public $slug                = "";
    public $singular_label      = "";
    public $supports            = array();
    
    private $is_first_metabox   = true; 
    private $current_post_data  = array();

    function __construct () {
        global $wpc_content_types;

//  SET DEFAULTS
        if ( empty($this->id) )             $this->id               = strtolower ( get_class($this) );

        if ( empty($this->label) )          $this->label            = $this->id . "s";
        if ( empty($this->singular_label) ) $this->singular_label   = $this->id;
        if ( empty($this->slug) )           $this->slug             = $this->id;

        if ( empty($this->supports) )       $this->supports         = array ('title','editor');


        if ( in_array($this->id, get_post_types()) ) {
            die ("wpc content_type \"$this->id\" is not unique");

            return ;
        } else {
            $wpc_content_types[$this->id] = $this;
        }

//  REGISTER POST TYPE
        register_post_type ($this->id, array(
            'label' => ucfirst($this->label),
            'singular_label' => ucfirst($this->singular_label),
            'public' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => 5, 
            '_builtin' => false,
            'rewrite' => array("slug" => $this->slug),
            'query_var' => $this->slug, 
            'supports' => $this->supports,
            'register_meta_box_cb' => array(&$this, "add_meta_boxes")
        ) );

//  ADD HOKKS
        add_action ("save_post",                    array(&$this, "save_post") );
        add_action ("wp_insert_post",               array(&$this, "wp_insert_post") );
        add_action ("wp_update_post",               array(&$this, "wp_update_post") );
    //  add_action ("delete_post",                  array(&$this, "delete_post") ); // NOT ACTUALLY NEEDED - RELATED POSTMETA GETS DELETED AUTOMATICALLY

        add_action ('admin_print_scripts',          array($this, "custom_print_scripts") );
        add_action ('admin_print_styles',           array($this, "custom_print_styles") );
        
        
        add_filter( "the_content",  array($this, "the_content") );
    }

    function the_content ($content) {
        global $post;
        
        foreach (glob(__DIR__ . "/../content_overrides/" . $post->post_type . ".php") as $filename) {
            $content = _compile($filename);
        }
        
        return $content;
    }

    function custom_print_scripts () {

    }

    function custom_print_styles () {

    }

    function first_metabox () {
        if ($this->is_first_metabox == true) {
            $this->is_first_metabox = false;
        ?>
            <script type="text/javascript">
                function check_text_input_value(event) {
                    var input = jQuery(this);
                    var label = jQuery("label.wpc_hint[for='" + input.attr('id') + "']");

                    if ( !(event.type == "keydown" && event.keyCode != 9) && input.val() == "" ){
                        label.addClass("show_full");
                    } else if (    event.keyCode >= 48
                                && event.keyCode != 91
                                && event.keyCode != 93) {
                        label.removeClass("show_full");
                    }
                }

                jQuery(document).ready(function () {
                    jQuery("body").delegate(".wpc_input_text", "focus keydown keyup change", check_text_input_value);
                    jQuery(".wpc_input_text").each(check_text_input_value);
                });

            </script>

        <?php

        } 
    }

    function add_meta_boxes ($post) {
        global $wpc_relationships;

        $this->load_post_data($post);

//  ADD METABOXES       
        foreach (glob(__DIR__ . "/../custom/metaboxes/" . $this->slug . "_*.php") as $filename) {
            $metabox_class_name = preg_replace("/\/?[^\/]+\/|\.php/", "", $filename);
            $metabox_class_id   = $metabox_class_name;

            if ( !startsWith($metabox_class_name, "__") ) {
                require_once $filename;

                $instance_name  = lcfirst($metabox_class_name);
                $$instance_name = new $metabox_class_name();
                
                $$instance_name->content_type = $this;

                add_meta_box(
                    $$instance_name->metabox_id, 
                    $$instance_name->label, 
                    array(&$$instance_name, "echo_metabox"), 
                    $this->id, 
                    $$instance_name->context, 
                    $$instance_name->priority, 
                    $this->current_post_data
                );
            }
        }


        foreach ($wpc_relationships as $wpc_relationship_key => $wpc_relationship) {
            if ($this->id == $wpc_relationship->post_type_from_id || $this->id == $wpc_relationship->post_type_to_id) {
                add_meta_box(
                    "$this->id-relationship", 
                    "Relationships", 
                    array("__GenericRelationship", "echo_relations_metabox" ), 
                    $this->id
                );

                break;
            }
        }
        
    }
    
    function admin_init() {

    }

    function load_post_data ($post) {
        $this->current_post_data = array();

        if( !empty($post) && $post->post_type == $this->id ) {
            $post_custom = get_post_custom();

            foreach ($this->fields as $field_key => $field) {
                if ( !empty($post_custom[$field_key]) ) {
                    if ( sizeof($post_custom[$field_key]) == 1 ) {
                        $this->current_post_data[$field_key] = $post_custom[$field_key][0];
                    } else {
                        $this->current_post_data[$field_key] = $post_custom[$field_key];
                    }
                } elseif ( !empty($this->fields[$field_key]->default) ) {
                    $this->current_post_data[$field_key] = $this->fields[$field_key]->default;
                } else {
                    $this->current_post_data[$field_key] = "";
                }
            }

            return true;
        }

        return false;
    }
    
    function delete_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            _ping();
        //  _log($post);


            return true;
        }

        return false;
    }
    
    
    function wp_update_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            _ping();
        //  _log($post);

            return true;
        }

        return false;
    }
    
    function save_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            _ping();
    //      _log($post);
    //      _log($_POST);

            $fields_to_update = array();
            $fields_to_remove = array();

            foreach ($this->fields as $field_key => $field) {
                if ( !empty($_POST["wpc_$field_key"]) ) {
                    $fields_to_update[$field_key] = $_POST["wpc_$field_key"];
                } elseif ( !empty($this->fields[$field_key]->default) ) {
                    $fields_to_update[$field_key] = $this->fields[$field_key]->default;
                } elseif ( isset($_POST["wpc_$field_key"]) ) {
                    $fields_to_remove[$field_key] = true;
                }
            }

            foreach ($fields_to_update as $field_key => $field_value) {
                update_post_meta($post_id, $field_key, $field_value);

                _log("update_post_meta($post_id, $field_key, \"$field_value\");");
            }

            foreach ($fields_to_remove as $field_key => $field_value) {
                delete_post_meta($post_id, $field_key);

                _log("delete_post_meta($post_id, $field_key);");
            }

            return true;
        }

        return false;
    }

    function wp_insert_post ($post_id) {
        $post = get_post($post_id);

        if( !empty($post) && $post->post_type == $this->id) {
            _ping();
        //  _log($post);


            return true;
        }

        return false;
    } 

}

?>