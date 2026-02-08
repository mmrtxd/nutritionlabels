<?php

/**
 * Enhanced database class with search and management features
 */

class NutritionLabels_DB_Extended
{
    
    public $wpdb;
    public $table_name;
    
    public function __construct() {
      global $wpdb;
      $this->wpdb = $wpdb;
      $this->table_name = $this->wpdb->prefix . 'nutrition_short_urls';
      
      // Ensure unique constraint exists
      $this->ensure_unique_constraint();
    }
    
    /**
     * Create database tables with unique constraint
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Check if table exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if (!$table_exists) {
            // Create new table with nutrition columns and unique constraint
            $sql = "CREATE TABLE $this->table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_id bigint(20) UNSIGNED NOT NULL,
                short_code varchar(10) NOT NULL,
                ingredients TEXT NOT NULL DEFAULT '',
                calories MEDIUMINT(5) UNSIGNED NOT NULL DEFAULT 0,
                kilojoules MEDIUMINT(6) UNSIGNED NOT NULL DEFAULT 0,
                carbohydrates DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0.00,
                sugar DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0.00,
                created_at datetime DEFAULT '0000-00-00 00:00:00',
                updated_at datetime DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY  (id),
                UNIQUE KEY short_code (short_code),
                UNIQUE KEY unique_product_id (product_id),
                KEY product_id_nutrition (product_id, calories, carbohydrates)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Table exists - add nutrition columns and unique constraint if they don't exist
            $this->add_nutrition_columns();
            $this->ensure_unique_constraint();
        }
    }
    
    /**
     * Add nutrition columns to existing table
     */
    private function add_nutrition_columns() {
        // Check if ingredients column exists
        $ingredients_exists = $this->wpdb->get_var(
            "SHOW COLUMNS FROM {$this->table_name} LIKE 'ingredients'"
        );
        
        if (!$ingredients_exists) {
            $this->wpdb->query("ALTER TABLE {$this->table_name} 
                ADD COLUMN ingredients TEXT NOT NULL DEFAULT '',
                ADD COLUMN calories MEDIUMINT(5) UNSIGNED NOT NULL DEFAULT 0,
                ADD COLUMN kilojoules MEDIUMINT(6) UNSIGNED NOT NULL DEFAULT 0,
                ADD COLUMN carbohydrates DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0.00,
                ADD COLUMN sugar DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0.00,
                ADD COLUMN updated_at datetime DEFAULT '0000-00-00 00:00:00'");
            
            // Add composite index
            $this->wpdb->query("ALTER TABLE {$this->table_name} 
                ADD INDEX product_id_nutrition (product_id, calories, carbohydrates)");
        }
    }
    
    /**
     * Get product ID by short code
     */
    public function get_product_id_by_shortcode($shortcode) {
        // Validate input
        if (empty($shortcode) || !ctype_alnum($shortcode) || strlen($shortcode) !== 5) {
            return false;
        }
        
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT product_id FROM $this->table_name WHERE short_code = %s LIMIT 1",
            $shortcode
        ));
    }
    
    /**
     * Get short code by product ID
     */
    public function get_shortcode_by_product_id($product_id) {
        // Validate input
        if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
            return false;
        }
        
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT short_code FROM $this->table_name WHERE product_id = %d LIMIT 1",
            $product_id
        ));
    }
    
    /**
     * Create short URL entry
     */
    public function create_short_url($product_id, $shortcode) {
        // Input validation
        if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0 ||
            empty($shortcode) || !ctype_alnum($shortcode) || strlen($shortcode) !== 5) {
            return false;
        }
        
        // Use transaction to prevent race conditions
        $this->wpdb->query('START TRANSACTION');
        
        try {
            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'product_id' => (int) $product_id,
                    'short_code' => $shortcode,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s')
            );
            
            if ($result === false) {
                $this->wpdb->query('ROLLBACK');
                return false;
            }
            
            $this->wpdb->query('COMMIT');
            return $result;
            
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Check if short code exists
     */
    public function shortcode_exists($shortcode) {
        // Validate input
        if (empty($shortcode) || !ctype_alnum($shortcode) || strlen($shortcode) !== 5) {
            return true; // Treat invalid as existing to prevent generation
        }
        
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE short_code = %s LIMIT 1",
            $shortcode
        ));
        
        return $count > 0;
    }
    
    /**
     * Ensure unique constraint on product_id to prevent duplicates
     */
    private function ensure_unique_constraint() {
    global $wpdb;
    $table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    
    // Check if unique constraint already exists
    $constraints = $wpdb->get_results("SELECT CONSTRAINT_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = '{$table_name}' 
        AND COLUMN_NAME = 'product_id' 
        AND CONSTRAINT_NAME != 'PRIMARY'");
    
    $has_unique = false;
    foreach ($constraints as $constraint) {
        if (strpos($constraint->CONSTRAINT_NAME, 'product_id') !== false) {
            $has_unique = true;
            break;
        }
    }
    
    // Add unique constraint if it doesn't exist
    if (!$has_unique) {
        // First, remove any duplicate entries
        $wpdb->query("
            DELETE n1 FROM {$table_name} n1
            INNER JOIN {$table_name} n2 
            WHERE n1.id > n2.id 
            AND n1.product_id = n2.product_id
        ");
        
        // Then add unique constraint
        $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE KEY unique_product_id (product_id)");
    }
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
    
    // Use REPLACE INTO to handle duplicates safely
    $sanitized_data['product_id'] = $product_id;
    
    // Remove updated_at from initial insert, will be set by REPLACE INTO
    $insert_data = $sanitized_data;
    $insert_data['created_at'] = current_time('mysql');
    
    error_log("Nutrition Labels: Using REPLACE INTO for product {$product_id}");
    
    // Build REPLACE INTO query manually to ensure proper handling
    $columns = array_keys($insert_data);
    $placeholders = array_fill(0, count($columns), '%s');
    
    $sql = "REPLACE INTO {$this->table_name} (" . implode(', ', $columns) . 
           ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $values = array_values($insert_data);
    
    $result = $this->wpdb->query($this->wpdb->prepare($sql, $values));
    
    error_log("Nutrition Labels: Replace result: " . ($result !== false ? 'success' : 'failed'));
    return $result;
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
