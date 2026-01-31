<?php
/**
 * Settings registration helper
 */

function nutrition_submit_button($text = 'Save Changes', $type = 'primary', $name = 'submit') {
    submit_button($text, $type, $name);
}

if (!function_exists('nutrition_selected')) {
    function nutrition_selected($value, $current) {
        return $value === $current ? 'selected="selected"' : '';
    }
}

// Helper function to generate select options for character set
function character_set_options($current_set) {
    $options = array(
        'alphanumeric' => 'Alphanumeric (A-Z, 0-9)',
        'numeric' => 'Numeric (0-9 only)'
    );
    
    $html = '';
    foreach ($options as $value => $label) {
        $selected = nutrition_selected($value, $current_set);
        $html .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
    }
    
    return $html;
}

// Helper function to generate size options
function qr_size_options($current_size) {
    $options = array(
        '300x300' => '300×300 (Small - Good for small labels)',
        '500x500' => '500×500 (Default - Standard size)',
        '800x800' => '800×800 (Large - Good for posters)'
    );
    
    $html = '';
    foreach ($options as $value => $label) {
        $selected = nutrition_selected($value, $current_size);
        $html .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
    }
    
    return $html;
}
?>