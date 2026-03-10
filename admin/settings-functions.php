<?php

/**
 * Copyright (c) 2026 - Markus Hammer - https://github.com/mmrtxd/
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */


if (!defined('ABSPATH')) {
  exit;
}

/**
 * Settings registration helper
 */

function nutrition_submit_button($text = 'Save Changes', $type = 'primary', $name = 'submit')
{
  submit_button($text, $type, $name);
}

if (!function_exists('nutrition_selected')) {
  function nutrition_selected($value, $current)
  {
    return (string) $value === (string) $current ? 'selected="selected"' : '';
  }
}

// Helper function to generate error correction level options
function qr_error_correction_options($current)
{
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
function qr_format_options($current_format)
{
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
function qr_size_options($current_size)
{
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

