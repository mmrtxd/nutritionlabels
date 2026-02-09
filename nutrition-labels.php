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

define('NUTRITION_LABELS_VERSION', '1.1.0');
define('NUTRITION_LABELS_DB_VERSION', '1.1.0');

define('NUTRITION_LABELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NUTRITION_LABELS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-db-extended.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-url.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/working-metabox.php';


class NutritionLabels
{

  public function __construct()
  {
    add_action('init', [$this, 'init']);
    add_action('admin_init', [$this, 'migrate_database']);
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

    // load the URL rewrite handling class
    NutritionLabels_URL::init();

    if (is_admin()) {
      require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/working-metabox.php';
      require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/class-nutrition-labels-admin-extended.php';
      require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-qr.php';
      new NutritionLabels_Admin_Extended();
    }
  }

  public static function migrate_database()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'nutrition_short_urls';

    $installed_db_version = get_option('nutrition_labels_db_version', '0.0.0');

    // Example migration: Add url_prefix column
    if (version_compare($installed_db_version, '1.1.0', '<')) {
      $column = $wpdb->get_var("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND COLUMN_NAME = 'url_prefix'
        ");

      if (!$column) {
        $wpdb->query("
                ALTER TABLE {$table}
                ADD COLUMN url_prefix VARCHAR(10) NOT NULL DEFAULT 'l'
            ");

        $prefix = get_option('url_prefix', 'l');
        $wpdb->query("
                UPDATE {$table}
                SET url_prefix = '{$prefix}'
            ");
      }

      // Update DB version
      update_option('nutrition_labels_db_version', '1.1.0');
    }

    // Future migrations can go here
    // if (version_compare($installed_db_version, '1.2.0', '<')) { ... }
  }
}

register_activation_hook(__FILE__, ['NutritionLabels', 'activate']);
register_deactivation_hook(__FILE__, ['NutritionLabels', 'deactivate']);

new NutritionLabels();
