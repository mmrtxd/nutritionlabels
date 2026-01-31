<?php

/**
 * Enhanced database class with search and management features
 */

class NutritionLabels_DB_Extended extends NutritionLabels_DB
{

  public function __construct() {
    parent::__construct();
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
