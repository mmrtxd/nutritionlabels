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

delete_option('qr_size');
delete_option('qr_format');
delete_option('qr_error_correction');
delete_option('nutrition_labels_db_version');
delete_option('nutrition_labels_delete_data_on_uninstall');
