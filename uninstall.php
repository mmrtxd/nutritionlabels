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


/**
 * Runs when the plugin is deleted from the WordPress admin.
 * Drops the custom table and removes all plugin options.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

global $wpdb;

if (get_option('nutrition_labels_delete_data_on_uninstall') === 'yes') {
  $table = $wpdb->prefix . 'nutrition_short_urls';
  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
  $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

delete_option('nutrition_labels_qr_size');
delete_option('nutrition_labels_qr_format');
delete_option('nutrition_labels_qr_error_correction');
// Pre-1.3.0 unprefixed names (only present if the 1.3.0 migration never ran).
// Same rule as the migration: delete only values that are clearly ours, since
// generic names like "qr_size" may belong to another plugin.
$legacy_qr_options = [
  'qr_size'             => ['300x300', '500x500', '800x800'],
  'qr_format'           => ['png', 'svg'],
  'qr_error_correction' => ['low', 'medium', 'quartile', 'high'],
];
foreach ($legacy_qr_options as $legacy_name => $allowed_values) {
  if (in_array(get_option($legacy_name), $allowed_values, true)) {
    delete_option($legacy_name);
  }
}
delete_option('nutrition_labels_db_version');
delete_option('nutrition_labels_delete_data_on_uninstall');
delete_option('nutrition_labels_use_subdomain');
delete_option('nutrition_labels_subdomain');
delete_option('nutrition_labels_subdomain_scheme');
