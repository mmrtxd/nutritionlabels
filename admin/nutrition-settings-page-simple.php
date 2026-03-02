<?php 

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
$current_prefix = get_option('url_prefix', 'l');
$current_length = get_option('short_code_length', 5);
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

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row">
            <label for="url_prefix"><?php esc_html_e('URL Prefix', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <input type="text" name="nutrition_labels[url_prefix]" id="url_prefix" value="<?php echo esc_attr($current_prefix); ?>" class="regular-text">
            <p class="description">
              <strong>Current:</strong> <code><?php echo home_url($current_prefix . '/[shortcode]'); ?></code>
              <br>
              <strong><?php esc_html_e('Warning:', 'nutrition-labels'); ?></strong> <?php esc_html_e('Changing this will make existing QR codes stop working!', 'nutrition-labels'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="short_code_length"><?php esc_html_e('Short Code Length', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <select name="nutrition_labels[short_code_length]" id="short_code_length">
              <option value="4" <?php nutrition_selected($current_length, 4); ?>>4 characters</option>
              <option value="5" <?php nutrition_selected($current_length, 5); ?>>5 characters (Default)</option>
              <option value="6" <?php nutrition_selected($current_length, 6); ?>>6 characters</option>
              <option value="7" <?php nutrition_selected($current_length, 7); ?>>7 characters</option>
              <option value="8" <?php nutrition_selected($current_length, 8); ?>>8 characters</option>
            </select>
            <p class="description">
              <?php esc_html_e('Minimum is 4 characters. Shorter codes may conflict more often.', 'nutrition-labels'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="character_set"><?php esc_html_e('Character Set', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <select name="nutrition_labels[character_set]" id="character_set">
              <option value="alphanumeric" <?php nutrition_selected(get_option('character_set', 'alphanumeric'), 'alphanumeric'); ?>>Alphanumeric</option>
              <option value="numeric">Numeric</option>
            </select>
            <p class="description"><?php esc_html_e('Choose allowed characters for short codes.', 'nutrition-labels'); ?></p>
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
      <li><strong>URL Format:</strong> <code><?php echo home_url($current_prefix . '/[shortcode]'); ?></code></li>
      <li><strong>QR Code Default:</strong> <code><?php echo get_option('qr_size', '500x500'); ?></code></li>
      <li><strong>Database Table:</strong> <code>wp_nutrition_short_urls</code></li>
    </ul>

    <?php if ($active_count > 0): ?>
      <div class="notice notice-info">
        <h4>⚠️ Important Notice About URL Prefix Changes</h4>
        <p>If you change the URL prefix, <strong>all existing QR codes will stop working</strong>. This affects:</p>
        <ul>
          <li>📱 All printed QR codes on wine bottles</li>
          <li>🏷️ Marketing materials with QR codes</li>
          <li>📋 Customer information QR codes</li>
          <li>🍷 Website QR code links</li>
        </ul>
        <p><strong>Before changing the prefix:</strong></p>
        <ul>
          <li>✅ Ensure you understand the impact</li>
          <li>🔄 Consider if you really need to change it</li>
          <li>📞 Have a plan to update any distributed materials</li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
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
