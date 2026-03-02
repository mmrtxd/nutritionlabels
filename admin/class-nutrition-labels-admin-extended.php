<?php

/**
 * Extended admin class with settings backend functionality
 */

class NutritionLabels_Admin_Extended
{

  private $db;

  public function __construct()
  {
    // Only proceed if WordPress functions are available
    if (!function_exists('wp_create_nonce') || !function_exists('add_action')) {
      return;
    }

    $this->db = new NutritionLabels_DB_Extended();

    // Register AJAX handlers
    add_action('wp_ajax_nutrition_search', array($this, 'ajax_search'));
    add_action('wp_ajax_nutrition_delete', array($this, 'ajax_delete'));
    add_action('wp_ajax_flush_rewrite_rules', array($this, 'ajax_flush_rewrite_rules'));
    add_action('wp_ajax_nutrition_qr_download', array($this, 'ajax_download_qr'));

    // Register admin menu pages
    add_action('admin_menu', array($this, 'register_admin_menu_pages'));

    // Register settings
    add_action('admin_init', array($this, 'register_settings'));

    // CSV Download
    add_action('admin_init', array($this, 'elabel_export_csv'));
  }

  public function register_admin_menu_pages()
  {
    // Add top-level menu with position just below Settings
    add_menu_page(
      __('Nutrition Labels Settings', 'nutrition-labels'),
      __('Nutrition Labels', 'nutrition-labels'),
      'manage_options',
      'nutrition_labels_main',
      array($this, 'render_settings_page'),
      'dashicons-food',
      80  // Position just below Settings (menu 80)
    );

    // Add Configuration submenu
    add_submenu_page(
      'nutrition_labels_main',
      __('Configuration', 'nutrition-labels'),
      __('Configuration', 'nutrition-labels'),
      'manage_options',
      'nutrition_labels_config',
      array($this, 'render_config_page')
    );

    // Add Database Management submenu
    add_submenu_page(
      'nutrition_labels_main',
      __('Database Management', 'nutrition-labels'),
      __('Database Management', 'nutrition-labels'),
      'manage_options',
      'nutrition_labels_db_management',
      array($this, 'render_db_management_page')
    );
  }

  public function register_settings()
  {
    register_setting('nutrition_labels_group', 'qr_size', array(
      'type' => 'string',
      'default' => '500x500',
      'sanitize_callback' => array($this, 'sanitize_qr_size')
    ));

    register_setting('nutrition_labels_group', 'short_code_length', array(
      'type' => 'integer',
      'default' => 5,
      'sanitize_callback' => 'absint'
    ));

    register_setting('nutrition_labels_group', 'character_set', array(
      'type' => 'string',
      'default' => 'alphanumeric',
      'sanitize_callback' => array($this, 'sanitize_character_set')
    ));

    register_setting('nutrition_labels_group', 'url_prefix', array(
      'type' => 'string',
      'default' => 'l',
      'sanitize_callback' => array($this, 'sanitize_url_prefix')
    ));
  }

  public function sanitize_url_prefix($input)
  {
    $input = sanitize_text_field($input);
    // Remove slashes and ensure valid prefix
    $input = rtrim(ltrim($input, '/'), '/');
    // Ensure it's not empty and contains only valid characters
    if (empty($input) || !preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
      return 'l'; // default fallback
    }
    return $input;
  }

  public function sanitize_qr_size($input)
  {
    $allowed = array('300x300', '500x500', '800x800');
    return in_array($input, $allowed) ? $input : '500x500';
  }

  public function sanitize_character_set($input)
  {
    $allowed = array('alphanumeric');
    return in_array($input, $allowed) ? $input : 'alphanumeric';
  }

  public function ajax_search()
  {
    check_ajax_referer('nutrition_search');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $search = sanitize_text_field($_POST['search']);
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $per_page = 50;

    $entries = $this->db->search_entries($search, $per_page, $page);

    wp_send_json(array(
      'entries' => $entries,
      'has_more' => count($entries) === $per_page,
      'current_page' => $page
    ));
  }

  public function ajax_delete()
  {
    check_ajax_referer('nutrition_delete', '_wpnonce');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $product_ids = array_map('absint', $_POST['product_ids']);

    if (empty($product_ids)) {
      wp_send_json_error('No products selected');
      return;
    }

    $deleted_count = 0;

    foreach ($product_ids as $product_id) {
      if (get_post($product_id)) {
        // Only delete the nutrition label entry, NOT the product
        $result = $this->db->delete_by_product_id($product_id);
        if ($result !== false) {
          $deleted_count++;
        }
      }
    }

    wp_send_json(array(
      'success' => true,
      'deleted_count' => $deleted_count,
      /* translators: %d: number of deleted entries */
      'message' => sprintf(_n(
        'Successfully deleted %d nutrition label entry',
        'Successfully deleted %d nutrition label entries',
        $deleted_count,
        'nutrition-labels'
      ), $deleted_count),
    ));
  }

  public function ajax_download_qr()
  {
    check_ajax_referer('nutrition_qr_download', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $product_id = absint($_POST['product_id']);
    if (!$product_id) {
      wp_send_json_error('Invalid product ID');
    }

    $nutrition_data = $this->db->get_complete_nutrition_data($product_id);
    if (empty($nutrition_data['short_code'])) {
      wp_send_json_error('No short URL for this product');
    }

    $prefix    = get_option('url_prefix', 'l');
    $short_url = home_url("/{$prefix}/{$nutrition_data['short_code']}");

    $data_uri = NutritionLabels_QR::generate_qr_code_base64($short_url);
    if ($data_uri === false) {
      wp_send_json_error('QR code generation failed');
    }

    $product      = get_post($product_id);
    $product_name = sanitize_file_name($product->post_title);

    wp_send_json_success(array(
      'url'      => $data_uri,
      'filename' => $product_name . '-nutrition-qr.png',
    ));
  }

  public function elabel_export_csv()
  {
    if (!current_user_can('manage_options')) {
      wp_die('You do not have permission to export CSV');
    }

    if (isset($_GET['export']) && $_GET['export'] === 'csv') {

      if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'nutrition_labels_export')) {
        wp_die(__('Invalid nonce', 'nutrition-labels'));
      }

      $db = new NutritionLabels_DB_Extended();
      $entries = $db->get_entries_for_export();

      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename="nutrition-labels.csv"');
      $output = fopen('php://output', 'w');

      // Headers
      fputcsv($output, ['Product ID', 'Product Title', 'Short Code', 'Prefix', 'Calories', 'Kilojoules', 'Carbs', 'Sugar']);

      foreach ($entries as $entry) {
        fputcsv($output, [
          $entry->product_id,
          get_the_title($entry->product_id),
          $entry->short_code,
          $entry->url_prefix,
          $entry->calories,
          $entry->kilojoules,
          $entry->carbohydrates,
          $entry->sugar
        ]);
      }

      fclose($output);
      exit;
    }
  }

  public function render_settings_page()
  {
    require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/nutrition-settings-page-simple.php';
  }

  public function render_config_page()
  {
    require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/nutrition-settings-page.php';
  }

  public function render_db_management_page()
  {
    // Ensure WordPress functions are available
    if (!function_exists('wp_create_nonce')) {
      wp_die('WordPress functions not available. Please contact administrator.');
    }
    require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/nutrition-db-management.php';
  }

  public static function get_settings_nonce()
  {
    return function_exists('wp_create_nonce') ? wp_create_nonce('update-options') : '';
  }

  public function ajax_flush_rewrite_rules()
  {
    check_ajax_referer('flush_rewrite_rules', '_wpnonce_flush');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    flush_rewrite_rules(false);

    wp_send_json(array(
      'success' => true,
      'message' => 'Rewrite rules flushed successfully!'
    ));
  }

  public static function handle_settings_submission()
  {
    if (function_exists('wp_verify_nonce') && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'update-options')) {
      // Save individual options
      if (isset($_POST['nutrition_labels']['url_prefix'])) {
        update_option('url_prefix', sanitize_text_field($_POST['nutrition_labels']['url_prefix']));
      }
      if (isset($_POST['nutrition_labels']['short_code_length'])) {
        update_option('short_code_length', absint($_POST['nutrition_labels']['short_code_length']));
      }
      if (isset($_POST['nutrition_labels']['character_set'])) {
        update_option('character_set', sanitize_text_field($_POST['nutrition_labels']['character_set']));
      }

      // Flush rewrite rules when URL prefix changes
      if (isset($_POST['nutrition_labels']['url_prefix'])) {
        flush_rewrite_rules(false);
      }

      echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'nutrition-labels') . '</p></div>';
    }
  }
}
