<?php

class NutritionLabels_DB_Extended
{

  public $wpdb;
  public $table_name;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_name = $wpdb->prefix . 'nutrition_short_urls';
  }

  /* -------------------------------------------------------------------------
     * TABLE CREATION
     * ---------------------------------------------------------------------- */

  public function create_tables()
  {
    $charset_collate = $this->wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$this->table_name} (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            url_prefix VARCHAR(10) NOT NULL DEFAULT 'l',
            short_code VARCHAR(10) NOT NULL,
            ingredients TEXT NOT NULL,
            calories MEDIUMINT(5) UNSIGNED NOT NULL DEFAULT 0,
            kilojoules MEDIUMINT(6) UNSIGNED NOT NULL DEFAULT 0,
            carbohydrates DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0.00,
            sugar DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY short_code (short_code),
            UNIQUE KEY unique_product_id (product_id),
            KEY product_id_nutrition (product_id, calories, carbohydrates)
        ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }

  /* -------------------------------------------------------------------------
     * SHORTCODE LOOKUPS
     * ---------------------------------------------------------------------- */

  public function get_product_id_by_shortcode($shortcode)
  {
    if (!$this->is_valid_shortcode($shortcode)) {
      return false;
    }

    return $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT product_id FROM {$this->table_name} WHERE short_code = %s LIMIT 1",
        $shortcode
      )
    );
  }

  public function get_shortcode_by_product_id($product_id)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    return $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT short_code FROM {$this->table_name} WHERE product_id = %d LIMIT 1",
        $product_id
      )
    );
  }

  public function shortcode_exists($shortcode)
  {
    if (empty($shortcode)) {
      return false;
    }

    return (bool) $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT 1 FROM {$this->table_name} WHERE short_code = %s LIMIT 1",
        $shortcode
      )
    );
  }

  /* -------------------------------------------------------------------------
     * CREATE SHORT URL
     * ---------------------------------------------------------------------- */

  public function create_short_url($product_id, $shortcode)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    if (!$this->is_valid_shortcode($shortcode)) {
      return false;
    }

    return $this->wpdb->insert(
      $this->table_name,
      array(
        'product_id' => (int) $product_id,
        'url_prefix' => get_option('url_prefix', 'l'),
        'short_code' => $shortcode,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
      ),
      array('%d', '%s', '%s', '%s')
    );
  }

  /* -------------------------------------------------------------------------
     * NUTRITION DATA
     * ---------------------------------------------------------------------- */

  public function get_nutrition_by_product_id($product_id)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    return $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM {$this->table_name} WHERE product_id = %d LIMIT 1",
        $product_id
      )
    );
  }

  public function save_nutrition_data($product_id, $data)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    $now = current_time('mysql');

    $exists = (int) $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM {$this->table_name} WHERE product_id = %d",
        $product_id
      )
    );

    $payload = array(
      'ingredients'   => ($data['ingredients'] instanceof NutritionLabelIngredientList)
                            ? json_encode($data['ingredients'])
                            : json_encode([]),
      'calories'      => absint($data['calories'] ?? 0),
      'kilojoules'    => absint($data['kilojoules'] ?? 0),
      'carbohydrates' => (float) ($data['carbohydrates'] ?? 0),
      'sugar'         => (float) ($data['sugar'] ?? 0),
      'updated_at'    => $now,
    );

    if ($exists > 0) {
      return $this->wpdb->update(
        $this->table_name,
        $payload,
        array('product_id' => (int) $product_id),
        array('%s', '%d', '%d', '%f', '%f', '%s'),
        array('%d')
      );
    }

    // INSERT path — created_at is guaranteed
    $payload['product_id'] = (int) $product_id;
    $payload['created_at'] = $now;

    return $this->wpdb->insert(
      $this->table_name,
      $payload,
      array('%s', '%d', '%d', '%f', '%f', '%s', '%d', '%s')
    );
  }


  public function delete_nutrition_data($product_id)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    return $this->wpdb->delete(
      $this->table_name,
      array('product_id' => (int) $product_id),
      array('%d')
    );
  }

  /**
   * Ensure a product has a unique short code
   */
  public function ensure_short_code($product_id)
  {
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
      return new WP_Error(
        'invalid_product_id',
        __('Invalid Product Id or product does not exist', 'nutrition-labels')
      );
    }

    // Check if product already has a short code
    $shortcode = $this->get_shortcode_by_product_id($product_id);
    if (!empty($shortcode)) {
      return $shortcode; // Already exists
    }

    // Generate unique 5-character alphanumeric code
    $tries = 0;

    // Get Options from Admin Settings
    // short_code_length
    // character_set

    $length = absint(get_option('short_code_length', 5));
    $charset = get_option('character_set', 'alphanumeric');

    if ($charset == 'alphanumeric') {
      $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }

    do {

      $tries++;
      $new_code = '';

      for ($i = 0; $i < $length; $i++) {
        $new_code .= $chars[wp_rand(0, strlen($chars) - 1)];
      }
    } while ($this->shortcode_exists($new_code) && $tries < 50);

    if ($tries >= 50) {
      // Failed to generate a unique code
      return new WP_Error(
        'shortcode_generation_failed',
        __('Unable to generate shortcode - exceeded 50 tries', 'nutrition-labels')
      );
    }

    // Insert the new shortcode into the existing row
    $updated = $this->wpdb->update(
      $this->table_name,
      ['url_prefix' => get_option('url_prefix', 'l'), 'short_code' => $new_code, 'updated_at' => current_time('mysql')],
      ['product_id' => $product_id],
      ['%s', '%s', '%s'],
      ['%d']
    );

    if ($updated == false) {
      return new WP_Error(
        'shortcode_db_update_failed',
        __('Shortcode DB Update failed', 'nutrition-labels')
      );
    }

    return $new_code;
  }


  /* -------------------------------------------------------------------------
     * SEARCH & EXPORT
     * ---------------------------------------------------------------------- */

  public function search_entries($search = '', $per_page = 50, $page = 1)
  {
    $offset = ($page - 1) * $per_page;

    $where = '';
    $args  = array($offset, $per_page);

    if ($search !== '') {
      $like = '%' . $this->wpdb->esc_like($search) . '%';
      $where = "WHERE (ns.short_code LIKE %s OR p.post_title LIKE %s)";
      $args[] = $like;
      $args[] = $like;
    }

    $sql = "
        SELECT ns.*, p.post_title
        FROM {$this->table_name} ns
        LEFT JOIN {$this->wpdb->posts} p ON ns.product_id = p.ID
        {$where}
        ORDER BY ns.created_at DESC
        LIMIT %d, %d
        ";

    return $this->wpdb->get_results(
      $this->wpdb->prepare($sql, $args)
    );
  }

  public function get_all_entries($per_page = 50, $page = 1)
  {
    $per_page = max(1, (int) $per_page);
    $page     = max(1, (int) $page);
    $offset   = ($page - 1) * $per_page;

    $sql = "
        SELECT ns.*, p.post_title, p.post_type
        FROM {$this->table_name} ns
        LEFT JOIN {$this->wpdb->posts} p ON ns.product_id = p.ID
        WHERE p.post_type = 'product'
        ORDER BY ns.created_at DESC
        LIMIT %d, %d
    ";

    return $this->wpdb->get_results(
      $this->wpdb->prepare($sql, $offset, $per_page)
    );
  }

  public function count_all_entries()
  {
    $sql = "
        SELECT COUNT(*)
        FROM {$this->table_name} ns
        LEFT JOIN {$this->wpdb->posts} p ON ns.product_id = p.ID
        WHERE p.post_type = 'product'
    ";

    return (int) $this->wpdb->get_var($sql);
  }

  public function delete_by_product_id($product_id)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    return $this->wpdb->delete(
      $this->table_name,
      array('product_id' => (int) $product_id),
      array('%d')
    );
  }

  public function count_search_results($search = '')
  {
    $where = '';
    $args  = array();

    if ($search !== '') {
      $like = '%' . $this->wpdb->esc_like($search) . '%';
      $where = "WHERE (ns.short_code LIKE %s OR p.post_title LIKE %s)";
      $args[] = $like;
      $args[] = $like;
    }

    $sql = "
        SELECT COUNT(*)
        FROM {$this->table_name} ns
        LEFT JOIN {$this->wpdb->posts} p ON ns.product_id = p.ID
        {$where}
        ";

    return $this->wpdb->get_var(
      $args ? $this->wpdb->prepare($sql, $args) : $sql
    );
  }

  public function get_complete_nutrition_data($product_id)
  {
    if (!is_numeric($product_id) || $product_id <= 0) {
      return false;
    }

    // Prefer table data
    $row = $this->get_nutrition_by_product_id($product_id);

    if ($row) {
      $ingredient_list = new NutritionLabelIngredientList();
      if (!empty($row->ingredients)) {
        $ingredient_list->hydrate($row->ingredients);
        // hydrate() calls json_decode internally; invalid/plain-text rows are silently ignored
      }

      return array(
        'url_prefix'     => $row->url_prefix ?? '',
        'short_code'     => $row->short_code ?? '',
        'ingredients'    => $ingredient_list,
        'calories'       => (int) $row->calories,
        'kilojoules'     => (int) $row->kilojoules,
        'carbohydrates'  => (float) $row->carbohydrates,
        'sugar'          => (float) $row->sugar,
        'created_at'     => $row->created_at ?? '',
        'updated_at'     => $row->updated_at ?? '',
        'source'         => 'table',
      );
    }
  }


  public function get_entries_for_export()
  {
    return $this->wpdb->get_results("
            SELECT ns.*, p.post_title
            FROM {$this->table_name} ns
            LEFT JOIN {$this->wpdb->posts} p ON ns.product_id = p.ID
            WHERE p.post_type = 'product'
            ORDER BY ns.created_at DESC
        ");
  }

  private function is_valid_shortcode($shortcode)
  {
    $charset = get_option('character_set', 'alphanumeric');

    $pattern = '';

    switch ($charset) {
      case 'alphanumeric':
      default:
        $pattern = 'A-Za-z0-9';
        break;
        // You can add more character sets later if needed
    }
    // we need to check if the shortcode is in the length scope (4-8 chars long)
    if (!preg_match('/^[' . $pattern . ']{4,8}$/', $shortcode)) {
      return false;
    }

    return true;
  }
}
