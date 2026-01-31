<?php
/**
 * FIXED VERSION: Database operations for nutrition labels
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
        
        // Only create table if it doesn't exist (safer approach)
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if ($table_exists) {
            return; // Table exists, don't recreate
        }
        
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) UNSIGNED NOT NULL,
            short_code varchar(10) NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY short_code (short_code),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
    
    // New method: Clean up old/invalid entries
    public function cleanup_invalid_entries() {
        // Remove entries older than 1 year without valid products
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM $this->table_name 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                   AND product_id NOT IN (SELECT ID FROM {$this->wpdb->posts} WHERE post_type = 'product')",
                null
            )
        );
        
        return $result !== false;
    }
}