<?php
/**
 * Database operations for nutrition labels
 * Added security and stability improvements
 */

class NutritionLabels_DB {
    
    private $table_name;
    
    public function __construct() {
      global $wpdb;
      $this->wpdb = $wpdb;

      $this->table_name = $this->wpdb->prefix . 'nutrition_short_urls';
    }
    
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Check if table exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if (!$table_exists) {
            // Create new table with nutrition columns
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
                KEY product_id (product_id),
                KEY product_id_nutrition (product_id, calories, carbohydrates)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Table exists - add nutrition columns if they don't exist
            $this->add_nutrition_columns();
        }
    }
    
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
}
