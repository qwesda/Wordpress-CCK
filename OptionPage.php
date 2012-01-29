<?php

/**
 * Manages WP-CCK-flavored option pages.
 *
 * (c) Tobias Florek <tob@bytesandbutter.de>
 * See LICENSE for License information.
 */

class OptionPage {

 protected $slug = "";
 protected $title = "";
 protected $menu_name = "";
 protected $option_namespace = "";
 protected $option_group = "";

 /**
  * this hash holds the prefix to section mapping in the form {prefix=>object}.
  * the object is meant to be a subclass of OptionPage.
  */
 protected $sections = array();

 /**
   * instanciates a new option page.
   *
   * pass a hash with the following information.
   *  - 'title', the title of the generated page,
   *  - 'menu_name', the name of the menu entry,
   *  - 'slug', i.e.: /wp-admin/options-general.php?page=$slug,
   *  - 'req_cap', the required capability, defaults to manage_options,
   *  - 'option_namespace', the wp-settings' key, to use with get_options,
   *  - 'sections', a hash of the form {key_prefix => classname}.
   */
  function __construct($args = array()) {
    $defaults = array(
      'req_cap' => 'manage_options',
      'slug' => 'wp-cck',
      'title' => "",
      'menu_name' => "",
      'option_namespace' => "",
      'sections' => array() );
    $args = wp_parse_args($args, $defaults);

    foreach (array_keys($defaults) as $key) {
      if (! array_key_exists($key, $args)) {
        _log("OptionPage: key '$key' not recognized.");
        next;
      }
      if (is_array($args[$key]) && empty($args[$key])) {
        _log("OptionPage: nothing to do. Key $key is empty.");
        return;
      }
      if ($args[$key] == "") {
        _log("OptionPage: key '$key' has to be set!
          Not adding this option page.");
        return;
      }
      $this->$key = $args[$key];
    }

    foreach ($this->sections as $prefix=>$name) {
      $object = new $name($this->slug, $this->option_namespace, $prefix);
      $this->sections[$prefix] = $object;
    }

    // use the slug as option group. we have only one group, so this will work.
    $this->option_group = $args['slug'];

    add_action('admin_menu', array($this, 'admin_add_page'));
    add_action('admin_init', array($this, 'admin_init'));
  }

  function admin_add_page() {
    add_options_page($this->title, $this->menu_name, $this->req_cap,
      $this->slug, array($this, 'options_page'));
  }

  function options_page() {
    echo "<div>
      <h2>$this->title</h2>

      <form action='options.php' method='post'>";

    settings_fields($this->option_group);
    do_settings_sections($this->slug);

    echo "<input name='Submit' type='submit' value='".esc_attr('Save Changes')."' />
      </form></div>";
  }

  function admin_init() {
    register_setting($this->option_group, $this->option_namespace, array($this, 'validate'));

    foreach ($this->sections as $prefix=>$object)
      $object->section($this->slug);
  }

  function validate($options) {
    $new_options = array();
    foreach ($options as $key=>$val) {
      foreach ($this->sections as $prefix=>$object) {
        if (strpos($key, $prefix) === 0)
          $new_options[$key] = $object->validate(substr($key, strlen($prefix)), $options[$key]);
      }
    }
    return $new_options;
  }
}

?>
