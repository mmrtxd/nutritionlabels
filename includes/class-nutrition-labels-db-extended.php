<?php

/**
 * Enhanced database class with search and management features
 */

class NutritionLabels_DB_Extended extends NutritionLabels_DB
{

  public function __construct() {
    parent::__construct();
  }
  
  /**
   * Get nutrition data by product ID
   */
  public function get_nutrition_by_product_id($product_id) {
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
      return false;
    }
    
    return $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT short_code, ingredients, calories, kilojoules, 
              carbohydrates, sugar, created_at, updated_at
       FROM {$this->table_name} 
       WHERE product_id = %d LIMIT 1",
      $product_id
    ));
  }
  
  /**
   * Save or update nutrition data for a product
   */
  public function save_nutrition_data($product_id, $data) {
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
      return false;
    }
    
    // Validate and sanitize input data
    $sanitized_data = array(
      'ingredients' => !empty($data['ingredients']) ? wp_kses_post($data['ingredients']) : '',
      'calories' => !empty($data['calories']) ? absint($data['calories']) : 0,
      'kilojoules' => !empty($data['kilojoules']) ? absint($data['kilojoules']) : 0,
      'carbohydrates' => !empty($data['carbohydrates']) ? floatval($data['carbohydrates']) : 0.00,
      'sugar' => !empty($data['sugar']) ? floatval($data['sugar']) : 0.00,
      'updated_at' => current_time('mysql')
    );
    
    // Check if record exists
    $existing = $this->wpdb->get_var($this->wpdb->prepare(
      "SELECT id FROM {$this->table_name} WHERE product_id = %d LIMIT 1",
      $product_id
    ));
    
    if ($existing) {
      // Update existing record
      return $this->wpdb->update(
        $this->table_name,
        $sanitized_data,
        array('product_id' => $product_id),
        array('%s', '%d', '%d', '%f', '%f', '%s'),
        array('%d')
      );
    } else {
      // Insert new record
      $sanitized_data['product_id'] = $product_id;
      $sanitized_data['created_at'] = current_time('mysql');
      
      return $this->wpdb->insert(
        $this->table_name,
        $sanitized_data,
        array('%d', '%s', '%d', '%d', '%f', '%f', '%s', '%s')
      );
    }
  }
  
  /**
   * Get complete nutrition data including fallback to post meta for backward compatibility
   */
  public function get_complete_nutrition_data($product_id) {
    // Try to get from table first
    $nutrition_data = $this->get_nutrition_by_product_id($product_id);
    
    if ($nutrition_data) {
      return array(
        'short_code' => $nutrition_data->short_code,
        'ingredients' => $nutrition_data->ingredients,
        'calories' => $nutrition_data->calories,
        'kilojoules' => $nutrition_data->kilojoules,
        'carbohydrates' => $nutrition_data->carbohydrates,
        'sugar' => $nutrition_data->sugar,
        'created_at' => $nutrition_data->created_at,
        'updated_at' => $nutrition_data->updated_at
      );
    }
    
    // Fallback to post meta for backward compatibility
    return array(
      'short_code' => '',
      'ingredients' => get_post_meta($product_id, '_nutrition_ingredients', true),
      'calories' => get_post_meta($product_id, '_nutrition_calories', true),
      'kilojoules' => get_post_meta($product_id, '_nutrition_kilojoules', true),
      'carbohydrates' => get_post_meta($product_id, '_nutrition_carbohydrates', true),
      'sugar' => get_post_meta($product_id, '_nutrition_sugar', true),
      'created_at' => '',
      'updated_at' => ''
    );
  }
  
  /**
   * Delete nutrition data when product is deleted
   */
  public function delete_nutrition_data($product_id) {
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
      return false;
    }
    
    return $this->wpdb->delete(
      $this->table_name,
      array('product_id' => $product_id),
      array('%d')
    );
  }
  
  /**
   * Get all entries with nutrition data for export
   */
  public function get_entries_with_nutrition_for_export() {
    global $wpdb;
    
    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $sql = "SELECT ns.*, p.post_title, p.post_type
                FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                WHERE p.post_type = 'product'
                ORDER BY ns.created_at DESC";

    return $this->wpdb->get_results($sql);
  }

  public function search_entries($search = '', $per_page = 50, $page = 1)
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $where = "";
    $params = array();

    if (!empty($search)) {
      $where = $wpdb->prepare(
        "WHERE (short_code LIKE %s OR p.post_title LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
      );
      $params[] = '%' . $wpdb->esc_like($search) . '%';
      $params[] = '%' . $wpdb->esc_like($search) . '%';
    }

    $offset = ($page - 1) * $per_page;

    $sql = "SELECT ns.*, p.post_title, p.post_type
                FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                {$where}
                ORDER BY ns.created_at DESC
                LIMIT %d, %d";

    $results = $wpdb->get_results($wpdb->prepare($sql, array_merge(array($offset, $per_page), $params)));

    return $results;
  }

  public function get_all_entries($per_page = 50, $page = 1)
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $offset = ($page - 1) * $per_page;

    $sql = "SELECT ns.*, p.post_title, p.post_type
                FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                WHERE p.post_type = 'product'
                ORDER BY ns.created_at DESC
                LIMIT %d, %d";

    $results = $wpdb->get_results($wpdb->prepare($sql, array($offset, $per_page)));

    return $results;
  }

  public function count_all_entries()
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $sql = "SELECT COUNT(*) FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                WHERE p.post_type = 'product'";

    return $wpdb->get_var($sql);
  }

  public function fixme_relink($search)
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $sql = "SELECT COUNT(*) FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                WHERE p.post_type = 'product'
                AND (ns.short_code LIKE %s OR p.post_title LIKE %s)";

    return $wpdb->get_var($wpdb->prepare($sql, array($wpdb->esc_like($search), $wpdb->esc_like($search))));
  }

  public function delete_by_product_id($product_id)
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';

    return $wpdb->delete(
      $table_name,
      array('product_id' => $product_id),
      array('%d')
    );
  }

  public function get_total_entries()
  {
    return $this->count_all_entries();
  }

  public function get_entries_for_export()
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $sql = "SELECT ns.*, p.post_title, p.post_type
                FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                WHERE p.post_type = 'product'
                ORDER BY ns.created_at DESC";

    return $wpdb->get_results($sql);
  }

  public function count_search_results($search = '')
  {
    global $wpdb;

    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    $posts_table = $this->wpdb->prefix . 'posts';

    $where = "";
    if (!empty($search)) {
      $where = $wpdb->prepare(
        "WHERE (short_code LIKE %s OR p.post_title LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
      );
    }

    $sql = "SELECT COUNT(*) FROM {$table_name} ns
                LEFT JOIN {$posts_table} p ON ns.product_id = p.ID
                {$where}";

    return $wpdb->get_var($sql);
  }
}
