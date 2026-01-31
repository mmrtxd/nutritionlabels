<?php
/**
 * Simple meta box template with current settings integration
 */

// Get current settings for prefix
$current_prefix = get_option('url_prefix', 'l');
?>

<div class="nutrition-fields">
    <p><strong>Ingredient List:</strong></p>
    <?php
    wp_editor($nutrition_data['ingredients'], 'nutrition_ingredients', array(
        'textarea_name' => 'nutrition_ingredients',
        'textarea_rows' => 6,
        'media_buttons' => false,
        'teeny' => true
    ));
    ?>
    
    <p><strong>Calories (kcal):</strong></p>
    <input type="number" name="nutrition_calories" value="<?php echo esc_attr($nutrition_data['calories']); ?>" step="1" min="0" max="10000" style="width: 100%;">
    
    <p><strong>Kilojoules (kJ):</strong></p>
    <input type="number" name="nutrition_kilojoules" value="<?php echo esc_attr($nutrition_data['kilojoules']); ?>" step="1" min="0" max="40000" style="width: 100%;">
    
    <p><strong>Carbohydrates (g):</strong></p>
    <input type="number" name="nutrition_carbohydrates" value="<?php echo esc_attr($nutrition_data['carbohydrates']); ?>" step="0.1" min="0" max="1000" style="width: 100%;">
    
    <p><strong>Sugar (g):</strong></p>
    <input type="number" name="nutrition_sugar" value="<?php echo esc_attr($nutrition_data['sugar']); ?>" step="0.1" min="0" max="1000" style="width: 100%;">
    
    <?php if ($short_url): ?>
    <p><strong>Nutrition Label URL:</strong></p>
    <input type="text" value="<?php echo esc_url($short_url); ?>" readonly style="width: 100%; background: #f5f5f5;">
    <button type="button" id="download_qr_code" class="button" data-product-id="<?php echo get_the_ID(); ?>" style="margin-top: 10px;">Download QR Code</button>
    
    <?php if ($current_prefix !== 'l'): ?>
    <p style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #d63638; border-radius: 4px;">
        <strong>ℹ️ Custom URL Prefix Active:</strong> Your nutrition labels are using the prefix <code><?php echo esc_html($current_prefix); ?>/</code>.
    </p>
    <?php endif; ?>
    
    <?php if ($short_url): ?>
    <p><em>Save product to generate a short URL and QR code.</em></p>
    <?php else: ?>
    <p><em>Save product to generate a short URL and QR code.</em></p>
    <?php endif; ?>
</div>