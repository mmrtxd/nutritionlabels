<?php

/**
 * Plugin Name: Nutrition Labels
 * Plugin URI:  https://example.com/nutrition-labels
 * Description: Adds nutrition label management, shortcodes, and QR code generation for products.
 * Version:     1.0.0
 * Author:      Markus Hammer 
 * Author URI:  https://hammerwein.at
 * Text Domain: nutrition-labels
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.2
 * Requires at least: 5.0
 */

if (!defined('ABSPATH')) {
  exit;
}

define('NUTRITION_LABELS_VERSION', '1.0.0');
define('NUTRITION_LABELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NUTRITION_LABELS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-db-extended.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-url.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/working-metabox.php';

// load the URL rewrite handling class
NutritionLabels_URL::init();

class NutritionLabels
{

  public function __construct()
  {
    add_action('init', [$this, 'init']);
  }

  public static function activate()
  {
    $db = new NutritionLabels_DB_Extended();
    $db->create_tables();

    flush_rewrite_rules();
  }

  public static function deactivate()
  {
    flush_rewrite_rules();
  }

  public function init()
  {

    if (is_admin()) {
      require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/working-metabox.php';
      require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/class-nutrition-labels-admin-extended.php';
      require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-qr.php';
      new NutritionLabels_Admin_Extended();
    }
  }
}

register_activation_hook(__FILE__, ['NutritionLabels', 'activate']);
register_deactivation_hook(__FILE__, ['NutritionLabels', 'deactivate']);

new NutritionLabels();
