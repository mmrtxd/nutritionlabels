<?php
/**
 * Working nutrition meta box - simple and direct
 */

class Working_NutritionLabels_MetaBox
{
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'register_metabox'));
        add_action('save_post', array($this, 'save_data'), 10, 2);
        add_action('wp_ajax_download_qr_code', array($this, 'download_qr_code'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function register_metabox() {
        add_meta_box(
            'nutrition_labels',
            'Nutrition Information',
            array($this, 'render_metabox'),
            'product',
            'normal',
            'default'
        );
    }
    
    public function render_metabox($post) {
        $product_id = $post->ID;
        
        // Simple database query - no complex classes
        global $wpdb;
        $nutrition_data = $wpdb->get_row($wpdb->prepare(
            "SELECT short_code, ingredients, calories, kilojoules, carbohydrates, sugar 
             FROM {$wpdb->prefix}nutrition_short_urls 
             WHERE product_id = %d LIMIT 1",
            $product_id
        ));
        
        $current_prefix = get_option('url_prefix', 'l');
        $short_url = ($nutrition_data && !empty($nutrition_data->short_code)) ? 
            home_url("/{$current_prefix}/{$nutrition_data->short_code}") : '';
        ?>
        <div class="nutrition-fields">
            <p><strong>Ingredient List:</strong></p>
            <textarea name="nutrition_ingredients" rows="6" style="width: 100%;"><?php 
                echo esc_textarea($nutrition_data->ingredients ?? ''); 
            ?></textarea>
            
            <p><strong>Calories (kcal):</strong></p>
            <input type="number" name="nutrition_calories" value="<?php 
                echo esc_attr($nutrition_data->calories ?? 0); ?>" step="1" min="0" max="10000" style="width: 100%;">
            
            <p><strong>Kilojoules (kJ):</strong></p>
            <input type="number" name="nutrition_kilojoules" value="<?php 
                echo esc_attr($nutrition_data->kilojoules ?? 0); ?>" step="1" min="0" max="40000" style="width: 100%;">
            
            <p><strong>Carbohydrates (g):</strong></p>
            <input type="number" name="nutrition_carbohydrates" value="<?php 
                echo esc_attr($nutrition_data->carbohydrates ?? 0); ?>" step="0.1" min="0" max="1000" style="width: 100%;">
            
            <p><strong>Sugar (g):</strong></p>
            <input type="number" name="nutrition_sugar" value="<?php 
                echo esc_attr($nutrition_data->sugar ?? 0); ?>" step="0.1" min="0" max="1000" style="width: 100%;">
            
            <?php if ($short_url): ?>
            <p><strong>Nutrition Label URL:</strong></p>
            <input type="text" value="<?php echo esc_url($short_url); ?>" readonly style="width: 100%; background: #f5f5f5;">
            <?php endif; ?>
            
            <button type="button" id="download_qr_code" class="button" data-product-id="<?php 
                echo esc_attr($product_id); ?>" style="margin-top: 10px;">
                <?php echo $short_url ? 'Download QR Code' : 'Generate QR Code'; ?>
            </button>
            
            <?php if (!$short_url): ?>
            <p><em>Save product to generate short URL first.</em></p>
            <?php endif; ?>
            
            <?php wp_nonce_field('nutrition_labels_save', 'nutrition_labels_nonce'); ?>
        </div>
        <?php
    }
    
    public function save_data($post_id, $post) {
        if ($post->post_type !== 'product') return;
        if (!isset($_POST['nutrition_labels_nonce']) || !wp_verify_nonce($_POST['nutrition_labels_nonce'], 'nutrition_labels_save')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        
        global $wpdb;
        
        // Get existing nutrition record first
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nutrition_short_urls WHERE product_id = %d LIMIT 1",
            $post_id
        ));
        
        // Enhanced save logic with proper sanitization
        $ingredients = sanitize_textarea_field($_POST['nutrition_ingredients'] ?? '');
        $calories = absint($_POST['nutrition_calories'] ?? 0);
        $kilojoules = absint($_POST['nutrition_kilojoules'] ?? 0);
        $carbohydrates = max(0, floatval($_POST['nutrition_carbohydrates'] ?? 0.0));
        $sugar = max(0, floatval($_POST['nutrition_sugar'] ?? 0.0));
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $wpdb->prefix . 'nutrition_short_urls',
                array(
                    'ingredients' => $ingredients,
                    'calories' => $calories,
                    'kilojoules' => $kilojoules,
                    'carbohydrates' => $carbohydrates,
                    'sugar' => $sugar,
                    'updated_at' => current_time('mysql')
                ),
                array('product_id' => $post_id),
                array('%s', '%d', '%d', '%d', '%f', '%f', '%s')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $wpdb->prefix . 'nutrition_short_urls',
                array(
                    'product_id' => $post_id,
                    'ingredients' => $ingredients,
                    'calories' => $calories,
                    'kilojoules' => $kilojoules,
                    'carbohydrates' => $carbohydrates,
                    'sugar' => $sugar,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%d', '%d', '%d', '%f', '%f', '%s', '%s')
            );
        }
        
        // Generate short code if ingredients exist and no short code
        $short_code = !empty($existing->short_code) && !empty($ingredients) ? $existing->short_code : '';
        if (empty($short_code) && !empty($ingredients)) {
            $short_code = substr(md5($product_id . time()), 0, 5);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}nutrition_short_urls SET short_code = %s WHERE product_id = %d",
                $short_code, $post_id
            ));
        }
    }
    
    public function download_qr_code() {
        check_ajax_referer('nutrition_labels_nonce', 'nonce');
        if (!current_user_can('edit_products')) wp_die('Unauthorized');
        
        $product_id = absint($_POST['product_id']);
        if (!$product_id) wp_send_json_error('Invalid product ID');
        
        global $wpdb;
        $db_nutrition_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nutrition_short_urls WHERE product_id = %d LIMIT 1",
            $product_id
        ));
        
        if (!$db_nutrition_data || empty($db_nutrition_data->short_code)) {
            wp_send_json_error('No short URL for this product');
            return;
        }
        
        $nutrition_data = $db_nutrition_data;
        
        $prefix = get_option('url_prefix', 'l');
        $short_url = home_url("/{$prefix}/{$nutrition_data->short_code}");
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&format=png&data=" . urlencode($short_url);
        
        $product = get_post($product_id);
        $product_name = sanitize_file_name($product->post_title);
        
        wp_send_json_success(array(
            'url' => $qr_url,
            'filename' => $product_name . '-nutrition-qr.png',
            'short_url' => $short_url
        ));
    }
    
    public function enqueue_scripts($hook) {
        if ($GLOBALS['post_type'] !== 'product') return;
        
        wp_enqueue_script('nutrition-labels-admin', plugins_url('nutrition-labels/assets/js/admin.js'), array('jquery'), '1.0.0', true);
        wp_localize_script('nutrition-labels-admin', 'nutritionLabels', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nutrition_labels_nonce')
        ));
    }
}

// Initialize
if (is_admin()) {
    new Working_NutritionLabels_MetaBox();
}
