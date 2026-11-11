<?php

/**
 * Copyright (c) 2026 - Markus Hammer - https://github.com/mmrtxd/
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('ABSPATH')) {
  exit;
}

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

  /**
   * Capability for Database Management (list, search, delete, CSV export, QR
   * download of deleted products). With WooCommerce this includes shop
   * managers; plugin settings stay admin-only (manage_options).
   */
  public static function db_management_capability(): string
  {
    return class_exists('WooCommerce') ? 'manage_woocommerce' : 'manage_options';
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

    // Add Database Management submenu
    add_submenu_page(
      'nutrition_labels_main',
      __('Database Management', 'nutrition-labels'),
      __('Database Management', 'nutrition-labels'),
      self::db_management_capability(),
      'nutrition_labels_db_management',
      array($this, 'render_db_management_page')
    );
  }

  public function register_settings()
  {
    $options = [
      NutritionLabels_Settings::QR_SIZE,
      NutritionLabels_Settings::QR_FORMAT,
      NutritionLabels_Settings::QR_ERROR_CORRECTION,
    ];
    foreach ($options as $option) {
      register_setting('nutrition_labels_group', $option, array(
        'type'              => 'string',
        'default'           => NutritionLabels_Settings::default($option),
        'sanitize_callback' => fn($v) => NutritionLabels_Settings::sanitize($option, $v),
      ));
    }
  }

  public function ajax_delete()
  {
    check_ajax_referer('nutrition_delete', '_wpnonce');
    if (!current_user_can(self::db_management_capability())) {
      wp_die('Unauthorized');
    }

    $raw_ids     = isset($_POST['product_ids']) && is_array($_POST['product_ids']) ? wp_unslash($_POST['product_ids']) : [];
    $product_ids = array_filter(array_map('absint', $raw_ids));

    if (empty($product_ids)) {
      wp_send_json_error('No products selected');
      return;
    }

    $deleted_count = 0;

    foreach ($product_ids as $product_id) {
      // Only delete the nutrition label entry, NOT the product. Rows whose
      // product no longer exists (orphans) can be deleted too.
      $result = $this->db->delete_by_product_id($product_id);
      if ($result) {
        $deleted_count++;
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

    $product_id = absint($_POST['product_id']);
    if (!$product_id) {
      wp_send_json_error('Invalid product ID');
    }

    // edit_post is always denied for a post that no longer exists. Labels of
    // deleted products stay live (printed QR codes), so their QR code is still
    // available — to the same capability that guards Database Management.
    $product = get_post($product_id);
    $allowed = $product
      ? current_user_can('edit_post', $product_id)
      : current_user_can(self::db_management_capability());
    if (!$allowed) {
      wp_die('Unauthorized');
    }

    // Validate optional language code against the whitelist
    $lang_code = '';
    $raw_lang  = isset($_POST['lang_code']) ? sanitize_text_field($_POST['lang_code']) : '';
    if ($raw_lang !== '' && preg_match('/^[a-z]{2}$/', $raw_lang)) {
      $lang_map = NutritionLabels_URL::get_lang_map();
      if (isset($lang_map[$raw_lang])) {
        $lang_code = $raw_lang;
      }
    }

    $nutrition_data = $this->db->get_complete_nutrition_data($product_id);
    if (empty($nutrition_data['short_code'])) {
      wp_send_json_error('No short URL for this product');
    }

    $short_url = NutritionLabels_URL::get_short_url($product_id, $lang_code);
    if (!$short_url) {
      wp_send_json_error('Could not build short URL for this product');
    }

    $format   = NutritionLabels_Settings::qr_format();
    $data_uri = NutritionLabels_QR::generate_qr_code_base64($short_url, $format);
    if ($data_uri === false) {
      wp_send_json_error('QR code generation failed');
    }

    $title        = $product ? $product->post_title : (string) ($nutrition_data['product_title'] ?? '');
    $product_name = sanitize_file_name($title);
    if ($product_name === '') {
      $product_name = 'product-' . $product_id;
    }
    $lang_suffix  = $lang_code !== '' ? '-' . $lang_code : '';
    $filename     = $product_name . '-nutrition-qr' . $lang_suffix . '.' . $format;

    wp_send_json_success(array(
      'url'      => $data_uri,
      'filename' => $filename,
    ));
  }

  public function elabel_export_csv()
  {
    // Only act on an actual CSV export request for our page. This runs on every
    // admin_init — including admin-ajax.php requests from front-end visitors —
    // so it must bail out silently for everything else.
    if (($_GET['export'] ?? '') !== 'csv' || ($_GET['page'] ?? '') !== 'nutrition_labels_db_management') {
      return;
    }

    if (!current_user_can(self::db_management_capability())) {
      wp_die(esc_html__('You do not have permission to export CSV', 'nutrition-labels'));
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'nutrition_labels_export')) {
      wp_die(esc_html__('Invalid nonce', 'nutrition-labels'));
    }

    $entries = $this->db->get_entries_for_export();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nutrition-labels.csv"');
    $output = fopen('php://output', 'w');

    // Headers
    fputcsv($output, ['Product ID', 'Product Title', 'Short Code', 'Prefix', 'Calories', 'Kilojoules', 'Carbs', 'Sugar']);

    foreach ($entries as $entry) {
      fputcsv($output, [
        $entry->product_id,
        self::csv_safe((string) $entry->post_title),
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

  /**
   * Neutralises spreadsheet formula injection (OWASP): cells starting with
   * = + - @ TAB or CR are prefixed with a single quote so they are treated as text.
   */
  private static function csv_safe(string $value): string
  {
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
      return "'" . $value;
    }
    return $value;
  }

  public function render_settings_page()
  {
    require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/nutrition-settings-page-simple.php';
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
    return function_exists('wp_create_nonce') ? wp_create_nonce('nutrition_labels_settings') : '';
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
      'message' => __('Rewrite rules flushed successfully!', 'nutrition-labels')
    ));
  }

  public static function handle_settings_submission()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to change settings.', 'nutrition-labels'));
    }
    if (function_exists('wp_verify_nonce') && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'nutrition_labels_settings')) {
      // Form field key => option name
      $qr_fields = [
        'qr_size'             => NutritionLabels_Settings::QR_SIZE,
        'qr_format'           => NutritionLabels_Settings::QR_FORMAT,
        'qr_error_correction' => NutritionLabels_Settings::QR_ERROR_CORRECTION,
      ];
      foreach ($qr_fields as $field => $option) {
        if (isset($_POST['nutrition_labels'][$field])) {
          $value = sanitize_text_field(wp_unslash($_POST['nutrition_labels'][$field]));
          update_option($option, NutritionLabels_Settings::sanitize($option, $value));
        }
      }
      // Checkbox: only present in POST when checked; absence means unchecked.
      update_option(
        'nutrition_labels_delete_data_on_uninstall',
        isset($_POST['nutrition_labels']['delete_data_on_uninstall']) ? 'yes' : 'no'
      );

      // Subdomain e-label URL settings
      update_option(
        'nutrition_labels_use_subdomain',
        isset($_POST['nutrition_labels']['use_subdomain']) ? 'yes' : 'no'
      );
      if (isset($_POST['nutrition_labels']['subdomain'])) {
        $raw = sanitize_text_field($_POST['nutrition_labels']['subdomain']);
        // Strip scheme/path if user accidentally pastes a full URL
        $raw = preg_replace('#^https?://|/.*$#', '', $raw);
        // Hostnames only: letters, digits, dots and hyphens.
        $raw = strtolower(trim($raw, ". \t"));
        if ($raw !== '' && !preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', $raw)) {
          $raw = '';
        }
        update_option('nutrition_labels_subdomain', $raw);
      }
      $scheme = sanitize_text_field($_POST['nutrition_labels']['subdomain_scheme'] ?? 'https');
      update_option('nutrition_labels_subdomain_scheme', in_array($scheme, ['https', 'http'], true) ? $scheme : 'https');

      echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'nutrition-labels') . '</p></div>';
    }
  }
}
