<?php

/**
 * Refactored Nutrition Meta Box - UI only
 */

class Working_NutritionLabels_MetaBox
{
  private $db;

  public function __construct(NutritionLabels_DB_Extended $db)
  {
    $this->db = $db;

    add_action('add_meta_boxes', [$this, 'register_metabox']);
    add_action('save_post', [$this, 'save_data'], 10, 2);
    add_action('wp_ajax_download_qr_code', [$this, 'download_qr_code']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
  }

  /**
   * Register the meta box for products
   */
  public function register_metabox()
  {
    add_meta_box(
      'nutrition_labels',
      'Nutrition Information',
      [$this, 'render_metabox'],
      'product',
      'normal',
      'default'
    );
  }

  /**
   * Render the meta box fields
   */
  public function render_metabox($post)
  {
    $product_id = $post->ID;

    // Use the DB class to fetch data
    $nutrition_data = $this->db->get_complete_nutrition_data($product_id);

    $current_prefix = get_option('url_prefix', 'l');
    $short_url = !empty($nutrition_data['short_code'])
      ? home_url("/{$current_prefix}/{$nutrition_data['short_code']}")
      : '';
?>

    <div class="nutrition-fields">
      <p><strong>Ingredient List:</strong></p>
      <textarea name="nutrition_ingredients" rows="6" style="width: 100%;"><?php
                                                                            echo esc_textarea($nutrition_data['ingredients'] ?? '');
                                                                            ?></textarea>

      <p><strong>Calories (kcal):</strong></p>
      <input type="number" name="nutrition_calories" value="<?php
                                                            echo esc_attr($nutrition_data['calories'] ?? 0); ?>" step="1" min="0" max="10000" style="width: 100%;">

      <p><strong>Kilojoules (kJ):</strong></p>
      <input type="number" name="nutrition_kilojoules" value="<?php
                                                              echo esc_attr($nutrition_data['kilojoules'] ?? 0); ?>" step="1" min="0" max="40000" style="width: 100%;">

      <p><strong>Carbohydrates (g):</strong></p>
      <input type="number" name="nutrition_carbohydrates" value="<?php
                                                                  echo esc_attr($nutrition_data['carbohydrates'] ?? 0); ?>" step="0.1" min="0" max="1000" style="width: 100%;">

      <p><strong>Sugar (g):</strong></p>
      <input type="number" name="nutrition_sugar" value="<?php
                                                          echo esc_attr($nutrition_data['sugar'] ?? 0); ?>" step="0.1" min="0" max="1000" style="width: 100%;">

      <?php if ($short_url): ?>
        <p><strong>Nutrition Label URL:</strong></p>
        <input type="text" value="<?php echo esc_url($short_url); ?>" readonly style="width: 100%; background: #f5f5f5;">
        <button type="button" id="download_qr_code" class="button" data-product-id="<?php
                                                                                    echo esc_attr($product_id); ?>" style="margin-top: 10px;">
          Download QR Code
        </button>
      <?php else: ?>
        <p><em>Save product to generate short URL first.</em></p>
      <?php endif; ?>

      <?php wp_nonce_field('nutrition_labels_save', 'nutrition_labels_nonce'); ?>
    </div>
<?php
  }

  /**
   * Save meta box data (delegates to DB class)
   */
  public function save_data($post_id, $post)
  {
    if ($post->post_type !== 'product') return;
    if (
      !isset($_POST['nutrition_labels_nonce']) ||
      !wp_verify_nonce($_POST['nutrition_labels_nonce'], 'nutrition_labels_save')
    ) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Prepare sanitized data
    $data = [
      'ingredients'   => sanitize_textarea_field($_POST['nutrition_ingredients'] ?? ''),
      'calories'      => absint($_POST['nutrition_calories'] ?? 0),
      'kilojoules'    => absint($_POST['nutrition_kilojoules'] ?? 0),
      'carbohydrates' => max(0, floatval($_POST['nutrition_carbohydrates'] ?? 0.0)),
      'sugar'         => max(0, floatval($_POST['nutrition_sugar'] ?? 0.0)),
    ];

    // Save via DB class
    $this->db->save_nutrition_data($post_id, $data);

    // Ensure short code exists
    if (!empty($data['ingredients'])) {
      $this->db->ensure_short_code($post_id);
    }
  }

  /**
   * AJAX: download QR code for product
   */
  public function download_qr_code()
  {
    check_ajax_referer('nutrition_labels_nonce', 'nonce');
    if (!current_user_can('edit_products')) wp_die('Unauthorized');

    $product_id = absint($_POST['product_id']);
    if (!$product_id) wp_send_json_error('Invalid product ID');

    $nutrition_data = $this->db->get_complete_nutrition_data($product_id);

    if (empty($nutrition_data['short_code'])) {
      wp_send_json_error('No short URL for this product');
    }

    $prefix = get_option('url_prefix', 'l');
    $short_url = home_url("/{$prefix}/{$nutrition_data['short_code']}");
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&format=png&data=" . urlencode($short_url);

    $product = get_post($product_id);
    $product_name = sanitize_file_name($product->post_title);

    wp_send_json_success([
      'url'       => $qr_url,
      'filename'  => $product_name . '-nutrition-qr.png',
      'short_url' => $short_url,
    ]);
  }

  /**
   * Enqueue admin scripts
   */
  public function enqueue_scripts($hook)
  {
    if ($GLOBALS['post_type'] !== 'product') return;

    wp_enqueue_script(
      'nutrition-labels-admin',
      plugins_url('nutrition-labels/assets/js/admin.js'),
      ['jquery'],
      '1.0.0',
      true
    );

    wp_localize_script('nutrition-labels-admin', 'nutritionLabels', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('nutrition_labels_nonce'),
    ]);
  }
}

// Initialize
if (is_admin()) {
  $db = new NutritionLabels_DB_Extended();
  new Working_NutritionLabels_MetaBox($db);
}

?>
