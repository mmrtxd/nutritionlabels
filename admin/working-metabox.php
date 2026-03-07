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
      __('Nutrition Information', 'nutrition-labels'),
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

    // Resolve current ingredient list (empty list for new/old-text rows)
    $ingredient_list = ($nutrition_data && $nutrition_data['ingredients'] instanceof NutritionLabelIngredientList)
      ? $nutrition_data['ingredients']
      : new NutritionLabelIngredientList();

    $col_left  = [
      'ingredients' => __('Base Ingredients', 'nutrition-labels'),
      'conservants' => __('Preservatives', 'nutrition-labels'),
    ];
    $col_right = [
      'regulators'  => __('Acid Regulators', 'nutrition-labels'),
      'stabilizers' => __('Stabilizers', 'nutrition-labels'),
    ];
?>

    <style>
      .nutrition-ing-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }
      .nutrition-ing-full-width { grid-column: 1 / -1; }
      .nutrition-ing-group { margin-bottom: 16px; }
      .nutrition-ing-group legend { font-weight: 600; font-size: 13px; padding: 0 4px; }
      .nutrition-ing-group fieldset { border: 1px solid #ddd; padding: 8px 12px; border-radius: 3px; }
      .nutrition-ing-row { display: flex; align-items: center; padding: 4px 0; border-bottom: 1px solid #f0f0f0; }
      .nutrition-ing-row:last-child { border-bottom: none; }
      .nutrition-ing-label { flex: 0 0 180px; font-size: 13px; }
      .nutrition-ing-radios { display: flex; gap: 12px; flex-wrap: wrap; }
      .nutrition-ing-radios label { font-size: 12px; cursor: pointer; white-space: nowrap; }
      .nutrition-ing-radios input[type="radio"] { margin-right: 3px; }
    </style>

    <div class="nutrition-fields">
      <p><strong><?php esc_html_e('Ingredients:', 'nutrition-labels'); ?></strong></p>

      <div class="nutrition-ing-columns">

        <?php /* ---- Left column ---- */ ?>
        <div>
          <?php foreach ($col_left as $group_key => $group_label): ?>
            <?php $this->render_ing_group($group_key, $group_label, $ingredient_list); ?>
          <?php endforeach; ?>
        </div>

        <?php /* ---- Right column ---- */ ?>
        <div>
          <?php foreach ($col_right as $group_key => $group_label): ?>
            <?php $this->render_ing_group($group_key, $group_label, $ingredient_list); ?>
          <?php endforeach; ?>
        </div>

        <?php /* ---- Gases — full width ---- */ ?>
        <div class="nutrition-ing-full-width">
          <?php $this->render_ing_group('gases', __('Gases', 'nutrition-labels'), $ingredient_list); ?>
        </div>

      </div><!-- end .nutrition-ing-columns -->

      <p><strong><?php esc_html_e('Calories (kcal):', 'nutrition-labels'); ?></strong></p>
      <input type="number" name="nutrition_calories" value="<?php
                                                            echo esc_attr($nutrition_data['calories'] ?? 0); ?>" step="1" min="0" max="10000" style="width: 100%;">

      <p><strong><?php esc_html_e('Kilojoules (kJ):', 'nutrition-labels'); ?></strong></p>
      <input type="number" name="nutrition_kilojoules" value="<?php
                                                              echo esc_attr($nutrition_data['kilojoules'] ?? 0); ?>" step="1" min="0" max="40000" style="width: 100%;">

      <p><strong><?php esc_html_e('Carbohydrates (g):', 'nutrition-labels'); ?></strong></p>
      <input type="number" name="nutrition_carbohydrates" value="<?php
                                                                  echo esc_attr($nutrition_data['carbohydrates'] ?? 0); ?>" step="0.1" min="0" max="1000" style="width: 100%;">

      <p><strong><?php esc_html_e('Sugar (g):', 'nutrition-labels'); ?></strong></p>
      <input type="number" name="nutrition_sugar" value="<?php
                                                          echo esc_attr($nutrition_data['sugar'] ?? 0); ?>" step="0.1" min="0" max="1000" style="width: 100%;">

      <?php if ($short_url): ?>
        <p><strong><?php esc_html_e('Nutrition Label URL:', 'nutrition-labels'); ?></strong></p>
        <input type="text" value="<?php echo esc_url($short_url); ?>" readonly style="width: 100%; background: #f5f5f5;">
        <button type="button" id="download_qr_code" class="button" data-product-id="<?php
                                                                                    echo esc_attr($product_id); ?>" style="margin-top: 10px;">
          <?php esc_html_e('Download QR Code', 'nutrition-labels'); ?>
        </button>
      <?php else: ?>
        <p><em><?php esc_html_e('Save product to generate short URL first.', 'nutrition-labels'); ?></em></p>
      <?php endif; ?>

      <?php wp_nonce_field('nutrition_labels_save', 'nutrition_labels_nonce'); ?>
    </div>
<?php
  }

  /**
   * Render a single ingredient group fieldset.
   *
   * @param string                       $group_key       Group property name on NutritionLabelIngredientList
   * @param string                       $group_label     German fieldset legend
   * @param NutritionLabelIngredientList $ingredient_list Current ingredient state
   */
  private function render_ing_group(
    string $group_key,
    string $group_label,
    NutritionLabelIngredientList $ingredient_list
  ): void {
    $group_obj = $ingredient_list->$group_key;
?>
    <div class="nutrition-ing-group">
      <fieldset>
        <legend><?php echo esc_html($group_label); ?></legend>
        <?php foreach (get_object_vars($group_obj) as $ing_key => $current_type): ?>
          <?php
          $current_value = ($current_type instanceof IngredientType) ? $current_type->value : IngredientType::Nil->value;
          $label         = NutritionLabelIngredientList::getLabel($group_key, $ing_key);
          $enumber       = NutritionLabelIngredientList::getENumber($group_key, $ing_key);
          $code_label    = $enumber !== '' ? 'Code (' . $enumber . ')' : 'Code';
          $field_name    = 'nutrition_ing[' . esc_attr($group_key) . '][' . esc_attr($ing_key) . ']';
          $id_base       = 'ing_' . esc_attr($group_key) . '_' . esc_attr($ing_key);
          ?>
          <div class="nutrition-ing-row">
            <span class="nutrition-ing-label"><?php echo esc_html($label); ?></span>
            <div class="nutrition-ing-radios">
              <label>
                <input type="radio"
                  name="<?php echo esc_attr($field_name); ?>"
                  id="<?php echo esc_attr($id_base); ?>_text"
                  value="<?php echo esc_attr(IngredientType::Text->value); ?>"
                  <?php checked($current_value, IngredientType::Text->value); ?>>
                <?php esc_html_e('Text', 'nutrition-labels'); ?>
              </label>
              <?php if ($enumber !== ''): ?>
              <label>
                <input type="radio"
                  name="<?php echo esc_attr($field_name); ?>"
                  id="<?php echo esc_attr($id_base); ?>_code"
                  value="<?php echo esc_attr(IngredientType::Code->value); ?>"
                  <?php checked($current_value, IngredientType::Code->value); ?>>
                <?php echo esc_html($code_label); ?>
              </label>
              <?php endif; ?>
              <?php if (NutritionLabelIngredientList::isOrganicEligible($group_key, $ing_key)): ?>
              <label>
                <input type="radio"
                  name="<?php echo esc_attr($field_name); ?>"
                  id="<?php echo esc_attr($id_base); ?>_orgtext"
                  value="<?php echo esc_attr(IngredientType::OrgText->value); ?>"
                  <?php checked($current_value, IngredientType::OrgText->value); ?>>
                <?php esc_html_e('Bio', 'nutrition-labels'); ?>
              </label>
              <?php endif; ?>
              <label>
                <input type="radio"
                  name="<?php echo esc_attr($field_name); ?>"
                  id="<?php echo esc_attr($id_base); ?>_nil"
                  value="<?php echo esc_attr(IngredientType::Nil->value); ?>"
                  <?php checked($current_value, IngredientType::Nil->value); ?>>
                <?php esc_html_e('Nil', 'nutrition-labels'); ?>
              </label>
            </div>
          </div>
        <?php endforeach; ?>
      </fieldset>
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

    // Build structured ingredient list from POST
    $ingredient_list = new NutritionLabelIngredientList();
    if (isset($_POST['nutrition_ing']) && is_array($_POST['nutrition_ing'])) {
      $ing_post = [];
      foreach ($_POST['nutrition_ing'] as $group => $values) {
        if (is_array($values)) {
          $ing_post[$group] = [];
          foreach ($values as $key => $value) {
            $ing_post[$group][$key] = sanitize_text_field((string) $value);
          }
        }
      }
      $ingredient_list->hydrateFromPost($ing_post);
    }

    // Prepare sanitized data
    $data = [
      'ingredients'   => $ingredient_list,
      'calories'      => absint($_POST['nutrition_calories'] ?? 0),
      'kilojoules'    => absint($_POST['nutrition_kilojoules'] ?? 0),
      'carbohydrates' => max(0, floatval($_POST['nutrition_carbohydrates'] ?? 0.0)),
      'sugar'         => max(0, floatval($_POST['nutrition_sugar'] ?? 0.0)),
    ];

    // Save via DB class
    $this->db->save_nutrition_data($post_id, $data);

    // Ensure short code exists if any ingredient is selected
    if ($ingredient_list->toDisplayString() !== '') {
      $this->db->ensure_short_code($post_id);
    }
  }

  /**
   * AJAX: download QR code for product
   */
  public function download_qr_code()
  {
    check_ajax_referer('nutrition_labels_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_die('Unauthorized');

    $product_id = absint($_POST['product_id']);
    if (!$product_id) wp_send_json_error('Invalid product ID');

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

    wp_send_json_success([
      'url'       => $data_uri,
      'filename'  => $product_name . '-nutrition-qr.png',
      'short_url' => $short_url,
    ]);
  }

  /**
   * Enqueue admin scripts
   */
  public function enqueue_scripts($hook)
  {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') return;

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

