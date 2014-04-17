<?php
/**
 * Plugin Name: wp-fields
 * Description: Wordpress Custom Fields Plugin
 */

if(!class_exists('Spyc')) {
  require_once('lib/spyc.php');
}

class ftFields {

  private $config = array();

  function __construct() {
    $this->config_path =   WP_CONTENT_DIR . '/fields';

    // We want the init to run after the default 10 to give themes a chance to config
    add_action('load-post.php', array($this, 'init'));
    add_action('load-post-new.php', array($this, 'init'));

    // Allow theme to override config path
    add_action('ft_fields_path', array($this, 'set_path'));

    // Save
    add_action('save_post', array($this, 'save_meta'));

    // Assets
    add_action('admin_init', array($this, 'load_assets'));

    $this->parse();
  }

  private function parse() {
    if(!file_exists($this->config_path)) {
      return false;
    }

    foreach (glob($this->config_path . "/*.yaml") as $filename) {
      $this->config[] = spyc_load_file($filename);
    }
  }

  function init() {

    foreach($this->config as $mid => $meta) {
      $post_type = isset($meta['post_type']) ? $meta['post_type'] : 'post';
      $context = isset($meta['context']) ? $meta['context'] : 'advanced';
      $priority = isset($meta['priority']) ? $meta['priority'] : 'default';

      add_meta_box('ft_fields' . $meta['id'], $meta['title'], array($this, 'render_meta_box'), $post_type, $context, $priority, array($mid));
    }
  }

  function load_assets() {
    wp_register_style('ft_fields', plugins_url('assets/fields.css', __FILE__ ));
    wp_enqueue_style('ft_fields');
  }

  function render_meta_box($post, $data) {
    $meta = $this->config[$data['args'][0]];
    $existing = get_post_meta($post->ID);
  
    foreach($meta['inputs'] as $input) {
      if(method_exists($this, "render_{$input['type']}_input")) {
        call_user_func_array(array($this, "render_{$input['type']}_input"), array($post, $meta, $existing, $input));
      }
    }
  }

  function set_path($path) {
    if(!file_exists($path)) {
      return false;
    }

    $this->config_path = $path;

    $this->parse();
  }

  function save_meta($post_id) {
    if(wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return;
    }

    foreach($this->config as $meta) {
      foreach($meta['inputs'] as $input) {
        $slug = "ft_fields_{$meta['id']}_{$input['name']}";

        if(isset($_POST[$slug])) {
          update_post_meta($post_id, $slug, sanitize_text_field($_POST[$slug]));
        }
      }
    }
  }

  // Render methods
  function render_text_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$meta['id']}_{$input['name']}";

    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? "placeholder=\"{$input['placeholder']}\"" : "";
    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? "value=\"{$existing[$slug][0]}\"" : "value=\"\"";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    echo "<input type=\"text\" class=\"ft-fields-input ft-fields-input-text\" name=\"{$slug}\" id=\"{$slug}\" autocomplete=\"off\" {$placeholder} {$value} {$required} />";
    echo '</p>';
  }

  function render_password_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$meta['id']}_{$input['name']}";

    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? "placeholder=\"{$input['placeholder']}\"" : "";
    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? "value=\"{$existing[$slug][0]}\"" : "value=\"\"";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    echo '<p>';
    
    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    echo "<input type=\"password\" class=\"ft-fields-input ft-fields-input-password\" name=\"{$slug}\" id=\"{$slug}\" autocomplete=\"off\" {$placeholder} {$value} {$required} />";
    echo '</p>';
  }
}

$ftFields = new ftFields;