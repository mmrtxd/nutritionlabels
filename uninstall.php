<?php

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
