<?php

/**
 * Baseclass for WP-CCK-flavored option sections.
 *
 * (c) Tobias Florek <tob@bytesandbutter.de>
 * See LICENSE for License information.
 */

abstract class OptionSection {

  /**
   * the title for the section.
   */
  protected $section_title = "";

  /**
   * the introductionary text for the section, if no section_text() method is
   * implemented.
   */
  protected $section_intro_text = "";




  /**
   * the settings_section ID.
   */
  protected $section_id = "";

  /**
   * the key namespace, i.e. the name of the base hash for the wp-settings API.
   */
  protected $key_namespace = "";

  /**
   * the key prefix.
   */
  protected $key_prefix = "";

  /**
   * The option keys with the validation, which can be one of the magic values
   * below or a function name. In that case, it will delegate to this function.
   * Pass an array like ($this, func) to call a method.
   * It has the form { $key_prefix + "name" => validation }.
   *
   * The magical validations are:
   *  - "true", does not perform a check,
   *  - "/regex/", a regex to check it for, discard if it does not match.
   *
   * The following is not yet implemented, but maybe should.
   * "s/pat/repl/", perform a sed-like replace and use the replacement.
   *
   * (maybe some form of chaining should be possible, to check, whether
   * something matches, and then replaceing parts of it.)
   */
  protected $option_keys_validation = array();

  /**
   * the name of the settings page, as in do_settings.
   */
  protected $options_slug = "";

  /**
   * the old options array. this is here only for caching.
   */
  protected $old_options = array();



  function __construct($options_slug, $key_namespace, $key_prefix) {
    $this->key_namespace = $key_namespace;
    $this->key_prefix = $key_prefix;
    $this->options_slug = $options_slug;
  }

  /**
   * This function builds the section.
   *
   * The introductionary text will be output by section_intro.
   */
  function section($settings_id) {
    $intro = array($this, 'section_intro');
    if (method_exists($this, 'section_text'))
      $intro = array($this, 'section_text');

    // maybe this should be unique per call to allow multiple (identical?)
    // sections...
    $this->section_id = "wpc_" + get_class();

    add_settings_section($this->section_id, $this->section_title, $intro, $settings_id);
    $this->options();
  }

  /**
   * simply echo section_intro_text
   */
  function section_intro () {
    echo $this->section_intro_text;
  }


  /**
   * This function gets called for the options that are handled by this section.
   *
   * When overwriting, note, that it has to return an empty string to unset an
   * option.
   */
  function validate($key, $option) {
    if (! array_key_exists($key, $this->option_keys_validation))
      // assume no validation needed, if key not found
      return $option;

    $validate = $this->option_keys_validation[$key];

    if ($validate === "true")
      return $option;

    // check, whether it is a pattern /pattern/
    if (preg_match('^/.*/$', $validate)) {
      $pat = substr($validate, 1, -1);
      if (preg_match($pat, $option))
        return $option;
      else
        return "";
    }

    // assume $validate is a method
    if (is_callable($validate))
      return call_user_func($validate, $key, $option);

    // something invalid. return empty string.
    return "";

  }

  /**
   * This function should build the options using wp-settings API. It has to
   * store the keys and the validation in the option_keys_validation hash.
   *
   * In detail, it will be called after a add_settings_section call (see
   * section()). It will likely call add_settings_field and friends.
   *
   * Note the helper functions below, which take care of most details.
   */
  abstract function options();


  /**
   * This is a wrapper around add_settings_field.
   * It will add the settings field and install the printing callback.
   */
  function add_field($name, $label, $cb, $additional_args = array()) {
    $key = $this->key_prefix.$name;

    if (empty($this->old_options))
      $this->old_options = get_option($this->key_namespace);
    if (array_key_exists($key, $this->old_options))
      $old_option = $this->old_options[$key];
    else
      $old_option = "";

    $cbargs = array_merge(array($name, $key, $old_option), $additional_args);
    $cb = function () use ($cb, $cbargs) { call_user_func_array($cb, $cbargs); };

    add_settings_field("$this->key_namespace-$key", $label, $cb,
      $this->options_slug, $this->section_id);
  }
  /**
   * this is a very rudimentary textfield.
   */
  function textfield($short_key, $key, $old_option) {
    echo "<input id='$this->key_namespace-$key'
      name='$this->key_namespace[$key]'
      value='$old_option'
      size='40' type='text' />";
  }

  /**
   * this is a very rudimentary textarea.
   */
  function textarea($short_key, $key, $old_option) {
    echo "<textarea id='$this->key_namespace-$key'
      name='$this->key_namespace[$key]'
      rows='7' cols='50' type='textarea'>$old_option</textarea>";
  }

  /**
   * this is a very rudimentary checkbox.
   */
  function checkbox($short_key, $key, $old_option) {
    if ($old_option)
      $checked = "checked='checked'";
    else
      $checked = "";

    echo "<input id='$this->key_namespace-$key'
      name='$this->key_namespace[$key]'
      type='checkbox' $checked />";
  }

  /**
   * this is a very rudimentary select box.
   */
  function selectbox ($short_key, $key, $old_option, $items) {
    echo "<select id='$this->key_namespace-$key'
      name='$this->key_namespace[$key]'>";

    foreach($items as $item) {
      $selected = ($old_option == $item) ? 'selected="selected"' : '';
      echo "<option value='$item' $selected>$item</option>";
    }
    echo "</select>";
  }
}

?>
