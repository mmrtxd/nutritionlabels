<?php

/**
 * URL handling for nutrition labels
 */

class NutritionLabels_URL
{

  private static $db;

  public static function init()
  {
    // Only proceed if WordPress functions are available
    if (!function_exists('add_action') || !function_exists('add_filter')) {
      return;
    }
    
    self::$db = new NutritionLabels_DB();
    add_action('init', array(__CLASS__, 'add_rewrite_rules'));
    add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
    add_action('template_redirect', array(__CLASS__, 'handle_short_url'));
  }

  private static function get_db()
  {
    if (!self::$db) {
      self::$db = new NutritionLabels_DB();
    }
    return self::$db;
  }

  public static function add_rewrite_rules()
  {
    add_rewrite_rule(
      '^l/([a-zA-Z0-9]{5})/?$',
      'index.php?nutrition_shortcode=$matches[1]',
      'top'
    );
  }

  public static function add_query_vars($query_vars)
  {
    $query_vars[] = 'nutrition_shortcode';
    return $query_vars;
  }

  public static function handle_short_url()
  {
    $shortcode = get_query_var('nutrition_shortcode');

    // Enhanced validation
    if (is_admin() || empty($shortcode) || !ctype_alnum($shortcode) || strlen($shortcode) !== 5) {
      return;
    }

    $db = self::get_db();
    $product_id = $db->get_product_id_by_shortcode($shortcode);

    if ($product_id) {
      self::display_nutrition_label($product_id);
      exit;
    }

    // If no valid short code found, let WordPress handle 404 normally
    return;
  }

  public static function get_short_code($product_id)
  {
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    $db = self::get_db();
    $short_code = $db->get_shortcode_by_product_id($product_id);

    if (!$short_code) {
      // Generate short code without rate limiting
      $short_code = self::create_short_code($product_id);
    }

    return $short_code;
  }

  public static function create_short_code($product_id)
  {
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    $db = self::get_db();

    // Add retry limit to prevent infinite loops
    $max_attempts = 10;
    $attempts = 0;

    do {
      $attempts++;

      if ($attempts > $max_attempts) {
        return false; // Prevent infinite loops
      }

      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $short_code = substr(str_shuffle($characters), 0, 5);

      // Additional validation to prevent problematic codes
      if (preg_match('/^[0OIl]/', $short_code)) {
        continue; // Skip codes with confusing characters
      }
    } while ($db->shortcode_exists($short_code));

    $result = $db->create_short_url($product_id, $short_code);

    return $result !== false ? $short_code : false;
  }

  public static function get_short_url($product_id)
  {
    $short_code = self::get_short_code($product_id);
    return home_url('/l/' . $short_code);
  }

  private static function display_nutrition_label($product_id)
  {
    $product = wc_get_product($product_id);

    if (!$product) {
      wp_die('Product not found');
    }

    // Enhanced security: validate all data
    $nutrition_data = array(
      'product_title' => sanitize_text_field($product->get_name()),
      'ingredient_list' => get_post_meta($product_id, '_nutrition_ingredients', true),
      'calories' => get_post_meta($product_id, '_nutrition_calories', true),
      'kilojoules' => get_post_meta($product_id, '_nutrition_kilojoules', true),
      'carbohydrates' => get_post_meta($product_id, '_nutrition_carbohydrates', true),
      'sugar' => get_post_meta($product_id, '_nutrition_sugar', true)
    );

    // Sanitize ingredient list specifically for display
    $nutrition_data['ingredient_list'] = wp_kses_post($nutrition_data['ingredient_list'], array(
      'a' => array(
        'href' => true,
        'title' => true,
      ),
      'br' => array(),
      'strong' => array(),
      'em' => array(),
      'p' => array()
    ));

    // Load template
    include NUTRITION_LABELS_PLUGIN_DIR . 'templates/nutrition-label-secure.php';
    exit;
  }
}

