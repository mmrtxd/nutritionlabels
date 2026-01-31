<?php

/**
 * CSV export handler for nutrition labels
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once NUTRITION_LABELS_PLUGIN_DIR . 'includes/class-nutrition-labels-db.php';

class NutritionLabels_Export
{

  private $db;

  public function __construct()
  {
    $this->db = new NutritionLabels_DB();
  }

  public function handle_export()
  {
    if (!current_user_can('manage_options')) {
      wp_die('You do not have permission to export nutrition labels.');
    }

    // Verify nonce
    // if (!wp_verify_nonce($_POST['_wpnonce'], 'export_nutrition_labels')) {
    //    wp_die('Security verification failed.');
    //}

    $filename = 'nutrition-labels-export-' . date('Y-m-d-His') . '.csv';

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=' . get_option('blog_charset') . '');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    $output = fopen('php://output', 'w');

    // CSV header
    fputcsv($output, array(
      'Product ID',
      'Product Name',
      'Short Code',
      'Ingredients',
      'Calories',
      'Kilojoules',
      'Carbohydrates',
      'Sugar',
      'Created At'
    ));

    // Get entries with search and pagination
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $per_page = 1000; // No pagination for exports
    $entries = $this->get_entries_for_export($search);

    foreach ($entries as $entry) {
      $product = get_post($entry->product_id);
      if ($product && $product->post_type === 'product') {
        fputcsv($output, array(
          $entry->product_id,
          $product->post_title,
          $entry->short_code,
          get_post_meta($entry->product_id, '_nutrition_ingredients', true),
          get_post_meta($entry->product_id, '_nutrition_calories', true),
          get_post_meta($entry->product_id, '_nutrition_kilojoules', true),
          get_post_meta($entry->product_id, '_nutrition_carbohydrates', true),
          get_post_meta($entry->product_id, '_nutrition_sugar', true),
          date('Y-m-d H:i:s', strtotime($entry->created_at))
        ));
      }
    }

    fclose($output);
    readfile('php://output');
    exit;
  }

  private function get_entries_for_export($search = '')
  {
    if (empty($search)) {
      return $this->db->get_all_entries();
    } else {
      return $this->db->search_entries($search);
    }
  }
}

// Handle the export request
if (isset($_POST['action']) && $_POST['action'] === 'export_nutrition_labels') {
  $export = new NutritionLabels_Export();
  $export->handle_export();
}

