<?php
/**
 * Plugin Name: First+Third Fields
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

      if(!is_array($post_type)) {
        $post_type = array($post_type);
      }

      foreach($post_type as $type) {
        add_meta_box('ft_fields' . $meta['id'], $meta['title'], array($this, 'render_meta_box'), $type, $context, $priority, array($mid));
      }
    }
  }

  function load_assets() {
    wp_register_style('ft_fields', plugins_url('assets/fields.css', __FILE__ ));
    wp_register_script('ft_fields', plugins_url('assets/fields.js', __FILE__ ));
    wp_enqueue_style('ft_fields');
    wp_enqueue_script('ft_fields');
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
        $slug = "ft_fields_{$input['name']}";

        if(isset($_POST[$slug])) {
          $value = $_POST[$slug];

          if(is_array($value)) {
            if(count($value) === 1) {
              $value = $value[0];
            } else {
              $value = json_encode($value);
            }
          }

          update_post_meta($post_id, $slug, $value);
        }
      }
    }
  }

  // Public API
  static function get_meta($id, $field = null) {
    $fields = get_post_meta($id);

    $field = 'ft_fields_' . $field;

    if($field === null) {
      return $fields;
    } else if(array_key_exists($field, $fields)) {
      $output = $fields[$field];

      if(is_array($output) && count($output) === 1) {
        $output = $output[0];
      }

      // Quickly check if json
      if(is_string($output)) {
        $output = self::parse_data($output);

        // json might come back as an array
        if(is_array($output) && count($output) === 1) {
          $output = $output[0];
        }
      }

      return $output;
    }

    return false;
  }

  function parse_data($data) {
    $decoded = json_decode($data);

    if(json_last_error() === JSON_ERROR_NONE) {
      $data = $decoded;
    }

    return $data;
  }

  // Render methods
  function render_text_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? "placeholder=\"{$input['placeholder']}\"" : "";
    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? "value=\"{$existing[$slug][0]}\"" : "value=\"\"";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    echo "<input type=\"text\" class=\"ft-fields-input ft-fields-input-text\" name=\"{$slug}\" id=\"{$slug}\" autocomplete=\"off\" {$placeholder} {$value} {$required} />";
    echo '</p>';
  }

  function render_password_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? "placeholder=\"{$input['placeholder']}\"" : "";
    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? "value=\"{$existing[$slug][0]}\"" : "value=\"\"";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    echo "<input type=\"password\" class=\"ft-fields-input ft-fields-input-password\" name=\"{$slug}\" id=\"{$slug}\" autocomplete=\"off\" {$placeholder} {$value} {$required} />";
    echo '</p>';
  }

  function render_media_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? "placeholder=\"{$input['placeholder']}\"" : "";
    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? "value=\"{$existing[$slug][0]}\"" : "value=\"\"";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    echo "<input type=\"text\" class=\"ft-fields-input ft-fields-input-media\" name=\"{$slug}\" id=\"{$slug}\" autocomplete=\"off\" {$placeholder} {$value} {$required} />";
    echo "<input type=\"button\" class=\"button ft-fields-button\" data-ft-fields-action=\"open-media\" data-ft-fields-target=\"{$slug}\" value=\"Select Media\" />";
    echo '</p>';
  }

  function render_select_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? $input['placeholder'] : "";

    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? (array) $this->parse_data($existing[$slug][0]) : array();
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    $multiple = (isset($input['multiple']) && $input['multiple'] === true) ? "multiple" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    echo "<select name=\"{$slug}[]\" id=\"{$slug}\" {$multiple}>";

    if(!empty($placeholder)) {
      echo "<option value=\"\" " . (!count($value) || in_array($option['value'], $value) ? 'selected' : '') . ">{$placeholder}</option>";
    }

    foreach($input['options'] as $option) {
      echo "<option value=\"{$option['value']}\" " . (in_array($option['value'], $value) ? 'selected' : '') . ">{$option['title']}</option>";
    }

    echo "</select>";
    echo '</p>';
  }

  function render_checkbox_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? json_decode($existing[$slug][0]) : array();
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    $multiple = (isset($input['multiple']) && $input['multiple'] === true) ? "multiple" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    foreach($input['options'] as $option) {
      echo "<div class=\"ft-fields-checkbox-group\"><input type=\"checkbox\" name=\"{$slug}[]\" id=\"{$slug}_{$option['value']}\" value=\"{$option['value']}\" " . (in_array($option['value'], $value) ? 'checked' : '') . "><label for=\"{$slug}_{$option['value']}\">{$option['title']}</label></div>";
    }

    echo '</p>';
  }

  function render_radio_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? $existing[$slug][0] : "";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    foreach($input['options'] as $option) {
      echo "<div class=\"ft-fields-radio-group\"><input type=\"radio\" name=\"{$slug}\" id=\"{$slug}_{$option['value']}\" value=\"{$option['value']}\" " . ($option['value'] === $value ? 'checked' : '') . "><label for=\"{$slug}_{$option['value']}\">{$option['title']}</label></div>";
    }

    echo '</p>';
  }

  function render_textarea_input($post, $meta, $existing, $input) {
    $slug = "ft_fields_{$input['name']}";

    $value = (isset($existing[$slug]) && isset($existing[$slug][0])) ? $existing[$slug][0] : "";
    $required = (isset($input['required']) && $input['required']) ? "data-ft-fields-required" : "";
    $placeholder = (isset($input['placeholder']) && !empty($input['placeholder'])) ? "placeholder=\"{$input['placeholder']}\"" : "";

    echo '<p>';

    if(isset($input['label']) && !empty($input['label'])) {
      echo "<label for=\"{$slug}\" class=\"ft-fields-label\">{$input['label']}</label>";
    }

    if(isset($input['description']) && !empty($input['description'])) {
      echo "<small class=\"ft-fields-description\">{$input['description']}</small>";
    }

    if(isset($input['wysiwyg']) && $input['wysiwyg']) {
      wp_editor($value, $slug);
    } else {
      echo "<textarea class=\"ft-fields-textarea\" name=\"{$slug}\" id=\"{$slug}\" {$placeholder}>{$value}</textarea>";
    }

    echo '</p>';
  }
}

$ftFields = new ftFields;
