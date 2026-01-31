<?php
/**
 * Settings registration with prefix change warnings
 */

function nutrition_labels_register_settings() {
    register_setting('nutrition_labels_group', 'url_prefix', array(
        'type' => 'string',
        'default' => 'l',
        'description' => 'URL prefix for nutrition labels. Warning: Changing this affects existing QR codes!'
    ));
    
    register_setting('nutrition_labels_group', 'short_code_length', array(
        'type' => 'integer',
        'default' => 5,
        'description' => 'Short code length. Minimum is 4.'
    ));
    
    register_setting('nutrition_labels_group', 'character_set', array(
        'type' => 'string',
        'default' => 'alphanumeric',
        'description' => 'Character set for short codes.'
    ));
}

function nutrition_labels_show_change_warning($new_length, $current_length, $active_count) {
    if ($new_length < $current_length && $active_count > 0) {
        $message = sprintf(
            '⚠️ Warning: Reducing from %d to %d characters may create conflicts with existing codes. ' .
            '%d existing codes will remain valid but %d new codes will be created.',
            $current_length, $new_length, 
            $active_count
        );
        
        add_settings_error('nutrition_labels', 'short_code_conflict', $message);
    }
    
    return true;
}

function nutrition_labels_validate_prefix_change($new_prefix) {
    $current_prefix = get_option('url_prefix', 'l');
    $active_entries = get_option('nutrition_labels_db_extended') ? 
        get_option('nutrition_labels_db_extended')->count_all_entries() : 0;
    
    if ($active_entries > 0 && $new_prefix !== $current_prefix) {
        add_settings_error('nutrition_labels', 'prefix_change_warning', sprintf(
            '⚠️ WARNING: Changing URL prefix from "%1$s" to "%2$s" will make %3$d existing QR codes stop working! ' .
            'This cannot be undone. Consider the consequences before proceeding.',
            $current_prefix, $new_prefix, $active_entries
        ));
        return false; // Block saving
    }
    
    return true; // Allow change
}

function nutrition_labels_add_settings_error($slug, $message) {
    add_settings_error('nutrition_labels', $slug, $message);
}

// Hook into WordPress settings API
add_action('admin_init', 'nutrition_labels_register_settings');

// Handle settings validation
add_action('pre_update_option_nutrition_labels', function($value, $option, $old_value) {
    if ($option === 'short_code_length') {
        $length = absint($value);
        if ($length < 4) {
            $value = 4; // Enforce minimum
        }
        return $value;
    }
});

// Sanitize and validate prefix input
add_filter('sanitize_option_nutrition_labels', function($value) {
    if (!is_string($value)) {
        return 'l'; // Default fallback
    }
    
    // Only allow alphanumeric and single forward slash
    $value = preg_replace('/[^a-zA-Z0-9\/]/', '', $value);
    $value = rtrim($value, '/');
    
    // Prevent empty prefix
    if (empty($value)) {
        return 'l'; // Default fallback
    }
    
    return sanitize_text_field($value);
});