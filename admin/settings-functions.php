<?php

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Settings registration helper
 */

function nutrition_submit_button($text = 'Save Changes', $type = 'primary', $name = 'submit') {
    submit_button($text, $type, $name);
}

if (!function_exists('nutrition_selected')) {
    function nutrition_selected($value, $current) {
        return (string) $value === (string) $current ? 'selected="selected"' : '';
    }
}

// Helper function to generate error correction level options
function qr_error_correction_options($current) {
    $options = array(
        'low'      => 'Low (L) — 7% recovery, fewest modules — best for small clean prints',
        'medium'   => 'Medium (M) — 15% recovery, good all-round balance',
        'quartile' => 'Quartile (Q) — 25% recovery, denser code',
        'high'     => 'High (H) — 30% recovery, most modules — use for labels that may get dirty or damaged',
    );

    $html = '';
    foreach ($options as $value => $label) {
        $selected = nutrition_selected($value, $current);
        $html .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
    }

    return $html;
}

// Helper function to generate format options for QR code format
function qr_format_options($current_format) {
    $options = array(
        'png' => 'PNG (Default — raster image, widest compatibility)',
        'svg' => 'SVG (Vector — crisp at any size)',
    );

    $html = '';
    foreach ($options as $value => $label) {
        $selected = nutrition_selected($value, $current_format);
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