<?php

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Simple settings page with warning system
 */

// Only render this page content if we're actually on the nutrition labels settings page
if (!isset($_GET['page']) || $_GET['page'] !== 'nutrition_labels_main') {
  return;
}

// Include required functions
if (file_exists(NUTRITION_LABELS_PLUGIN_DIR . 'admin/settings-functions.php')) {
  require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/settings-functions.php';
}

// Check if WordPress functions are available
if (!function_exists('settings_fields') || !function_exists('get_option')) {
  wp_die('WordPress functions not available. Please contact administrator.');
}

// Handle form submission through admin class
if (isset($_POST['submit-nutrition-settings']) && class_exists('NutritionLabels_Admin_Extended')) {
  NutritionLabels_Admin_Extended::handle_settings_submission();
}

// Get current settings
$current_qr_size        = get_option('qr_size', '500x500');
$current_qr_format      = get_option('qr_format', 'png');
$current_qr_correction  = get_option('qr_error_correction', 'low');
$db = new NutritionLabels_DB_Extended();
$active_count = $db->count_all_entries();
?>

<div class="wrap">
  <h1><?php esc_html_e('Nutrition Labels Settings', 'nutrition-labels'); ?></h1>
  <p class="description"><?php esc_html_e('Configure your nutrition labels plugin settings and manage QR code generation.', 'nutrition-labels'); ?></p>

  <form method="post" action="">
    <?php 
    // Use safe nonce generation through admin class
    if (class_exists('NutritionLabels_Admin_Extended')) {
      echo '<input type="hidden" name="_wpnonce" value="' . esc_attr(NutritionLabels_Admin_Extended::get_settings_nonce()) . '">';
    }
    ?>

    <?php require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/settings-functions.php'; ?>
    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row">
            <label for="qr_size">
              <?php esc_html_e('QR Code Size', 'nutrition-labels'); ?>
              <span class="nl-tooltip" data-tip="<?php esc_attr_e('Pixel dimensions of the downloaded QR image. For print use SVG format is recommended — it is resolution-independent and will be crisp at any size regardless of this setting.', 'nutrition-labels'); ?>">?</span>
            </label>
          </th>
          <td>
            <select name="nutrition_labels[qr_size]" id="qr_size">
              <?php echo qr_size_options($current_qr_size); ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="qr_format">
              <?php esc_html_e('QR Code Format', 'nutrition-labels'); ?>
              <span class="nl-tooltip" data-tip="<?php esc_attr_e('SVG is a vector format — edges stay perfectly sharp at any print size and is recommended for wine labels. PNG is a pixel-based image; choose a large size if using PNG for print.', 'nutrition-labels'); ?>">?</span>
            </label>
          </th>
          <td>
            <select name="nutrition_labels[qr_format]" id="qr_format">
              <?php echo qr_format_options($current_qr_format); ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="qr_error_correction">
              <?php esc_html_e('Error Correction', 'nutrition-labels'); ?>
              <span class="nl-tooltip" data-tip="<?php esc_attr_e('Higher correction levels add redundant data so the code can still scan if partially damaged, but produce a denser, more complex pattern. For small clean wine labels (18 mm) Low is recommended — it produces the fewest modules and is easiest to scan.', 'nutrition-labels'); ?>">?</span>
            </label>
          </th>
          <td>
            <select name="nutrition_labels[qr_error_correction]" id="qr_error_correction">
              <?php echo qr_error_correction_options($current_qr_correction); ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <?php esc_html_e('Delete Data on Uninstall', 'nutrition-labels'); ?>
          </th>
          <td>
            <label>
              <input type="checkbox" name="nutrition_labels[delete_data_on_uninstall]" id="delete_data_on_uninstall" value="1"
                <?php checked('yes', get_option('nutrition_labels_delete_data_on_uninstall', 'no')); ?>>
              <?php esc_html_e('Permanently delete all nutrition label data when the plugin is uninstalled', 'nutrition-labels'); ?>
            </label>
            <p class="description" style="color:#d63638;font-weight:500;">
              <?php esc_html_e('Warning: enabling this will drop the database table and all nutrition label records when the plugin is deleted. This cannot be undone.', 'nutrition-labels'); ?>
            </p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php 
if (function_exists('submit_button')) {
  submit_button('Save Settings', 'primary', 'submit-nutrition-settings'); 
} else {
  echo '<input type="submit" name="submit-nutrition-settings" value="Save Settings" class="button button-primary">';
}
?>
  </form>
  
  <div class="nutrition-labels-actions">
    <form method="post" action="">
      <?php wp_nonce_field('flush_rewrite_rules', '_wpnonce_flush'); ?>
      <input type="hidden" name="action" value="flush_rewrite_rules">
      <button type="submit" class="button button-secondary">🔄 Flush Rewrite Rules</button>
    </form>
    <p class="description">If short URLs are not working (404 errors), click this button to refresh WordPress rewrite rules.</p>
  </div>

  <script>
    jQuery(document).ready(function($) {
        $('form input[name="action"][value="flush_rewrite_rules"]').closest('form').submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'flush_rewrite_rules',
                    _wpnonce_flush: $('input[name="_wpnonce_flush"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error: Could not flush rewrite rules');
                }
            });
        });
    });
  </script>

  <div class="nutrition-labels-info">
    <h3><?php esc_html_e('Information', 'nutrition-labels'); ?></h3>
    <ul>
      <li><strong><?php esc_html_e('Current Entries:', 'nutrition-labels'); ?></strong> <?php printf(esc_html__('%d nutrition labels active', 'nutrition-labels'), $active_count); ?></li>
      <li><strong>URL Format:</strong> <code><?php echo esc_html(home_url('/' . NUTRITION_LABELS_URL_PREFIX . '/[shortcode]')); ?></code></li>
      <li><strong>QR Code Size:</strong> <code><?php echo esc_html(get_option('qr_size', '500x500')); ?></code></li>
      <li><strong>QR Code Format:</strong> <code><?php echo esc_html(strtoupper(get_option('qr_format', 'png'))); ?></code></li>
      <li><strong>Error Correction:</strong> <code><?php echo esc_html(ucfirst(get_option('qr_error_correction', 'low'))); ?></code></li>
      <li><strong>Database Table:</strong> <code>wp_nutrition_short_urls</code></li>
    </ul>
  </div>
</div>

<style>
  .nl-tooltip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 15px;
    height: 15px;
    background: #888;
    color: #fff;
    border-radius: 50%;
    font-size: 10px;
    font-weight: bold;
    cursor: help;
    position: relative;
    margin-left: 4px;
    vertical-align: middle;
    flex-shrink: 0;
  }

  .nl-tooltip::after {
    content: attr(data-tip);
    position: absolute;
    left: 22px;
    top: 50%;
    transform: translateY(-50%);
    background: #1d2327;
    color: #f0f0f1;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: normal;
    white-space: normal;
    width: 260px;
    line-height: 1.5;
    display: none;
    z-index: 9999;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
  }

  .nl-tooltip:hover::after {
    display: block;
  }

  .form-table th label {
    display: flex;
    align-items: center;
  }

  .form-table {
    margin-top: 20px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd;
    border-radius: 4px;
  }

  .form-table th {
    text-align: left;
    padding: 10px 10px 10px 20px;
    width: 200px;
  }

  .form-table td {
    padding: 10px 20px;
    vertical-align: middle;
  }

  .form-table select {
    width: 100%;
  }

  .form-table input {
    max-width: 300px;
  }

  .form-table .description {
    font-style: italic;
    color: #666;
    margin-top: 5px;
  }

  .nutrition-labels-info {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
  }

  .nutrition-labels-info h3 {
    margin-top: 0;
    margin-bottom: 15px;
  }

  .nutrition-labels-info ul {
    list-style: disc;
    margin-left: 20px;
  }

  .nutrition-labels-info code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
  }

  .notice {
    padding: 15px;
    margin: 15px 0;
    border-left: 4px solid #d63638;
    background: #fef7f1;
    border-radius: 4px;
  }

  .notice-info {
    border-left-color: #00a32a;
  }

  .notice-warning {
    border-left-color: #d63638;
  }

  .nutrition-labels-info strong {
    color: #d63638;
  }
</style>
