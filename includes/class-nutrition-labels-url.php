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

    self::$db = new NutritionLabels_DB_Extended();
    add_action('init', array(__CLASS__, 'add_rewrite_rules'));
    add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
    add_action('template_redirect', array(__CLASS__, 'handle_short_url'));
  }

  private static function get_db()
  {
    if (!self::$db) {
      self::$db = new NutritionLabels_DB_Extended();
    }
    return self::$db;
  }

  public static function add_rewrite_rules()
  {
    // Get configurable URL prefix
    $prefix = get_option('url_prefix', 'l');
    $charset = get_option('charset', 'alphanumeric');
    $preg = '';

    switch ($charset) {
      case 'alphanumeric':
        $preg = '[a-zA-Z0-9]';
        break;
      default:
        $preg = '[a-zA-Z0-9]';
        break;
    }
    add_rewrite_rule(
      '^' . $prefix . '/(' . $preg . '{4,8})/?$',
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
    if (is_admin() || empty($shortcode) || !ctype_alnum($shortcode)) {
      return;
    }

    $db = self::get_db();
    $product_id = $db->get_product_id_by_shortcode($shortcode);

    if ($product_id) {
      self::display_nutrition_label($product_id);
    }

    // If no valid short code found, let WordPress handle 404 normally
    return;
  }

  private static function display_nutrition_label($product_id)
  {
    // Get nutrition data from database
    $db = self::get_db();
    $nutrition_table_data = $db->get_complete_nutrition_data($product_id);

    if (!$nutrition_table_data) {
      wp_die(__('E-Label not found', 'nutrition_labels'));
    }
    // Enhanced security: validate all data
    $product_title = get_the_title($product_id);

    $ingredients_obj = $nutrition_table_data['ingredients'];
    $ingredient_display = ($ingredients_obj instanceof NutritionLabelIngredientList)
      ? esc_html($ingredients_obj->toDisplayString())
      : '';

    $nutrition_data = array(
      'product_title'  => sanitize_text_field($product_title),
      'ingredient_list' => $ingredient_display,
      'calories'        => $nutrition_table_data['calories'],
      'kilojoules'      => $nutrition_table_data['kilojoules'],
      'carbohydrates'   => $nutrition_table_data['carbohydrates'],
      'sugar'           => $nutrition_table_data['sugar'],
    );

    // Locate template — themes may override by placing a file at:
    // {theme}/nutrition-labels/nutrition-label-secure.php
    $template = locate_template('nutrition-labels/nutrition-label-secure.php');
    if (empty($template)) {
      $template = NUTRITION_LABELS_PLUGIN_DIR . 'templates/nutrition-label-secure.php';
    }

    // Allow programmatic override via filter
    $template = apply_filters('nutrition_labels_template', $template, $product_id);

    include $template;
    exit;
  }
}
