<?php

/**
 * Main plugin file with simplified settings integration
 */

if (!defined('ABSPATH')) {
  exit;
}

define('NUTRITION_LABELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NUTRITION_LABELS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-db.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-db-extended.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-url.php';
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-qr.php';

require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/class-nutrition-labels-admin-extended.php';



class NutritionLabels
{

  public function __construct()
  {
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    add_action('plugins_loaded', array($this, 'init'));
  }

  public function activate()
  {
    $db = new NutritionLabels_DB_Extended();
    $db->create_tables();

    flush_rewrite_rules();
  }

  public function deactivate()
  {
    flush_rewrite_rules();
  }

  public function init()
  {
    // Wait for WordPress to be fully loaded
    if (class_exists('WooCommerce') && function_exists('add_action')) {
      // Initialize URL handling first
      NutritionLabels_URL::init();
      // Initialize admin on 'init' hook with lower priority to ensure WordPress functions are available
      add_action('init', array($this, 'initialize_admin'), 20);
    }
  }

  public function initialize_admin()
  {
    // Only initialize in admin area
    if (is_admin()) {
      new NutritionLabels_Admin_Extended();
    }
  }
}

new NutritionLabels();
