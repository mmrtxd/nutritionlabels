<?php

/**
 * Settings page for configuration
 */

// Get current settings
$qr_size = get_option('qr_size', '500x500');
$short_code_length = get_option('short_code_length', 5);
$character_set = get_option('character_set', 'alphanumeric');
?>

<div class="wrap">
  <h1><?php esc_html_e('Nutrition Labels - Configuration', 'nutrition-labels'); ?></h1>

  <form method="post" action="options.php">
    <?php settings_fields('nutrition_labels_group'); ?>

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row">
            <label for="qr_size"><?php esc_html_e('QR Code Size', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <select name="qr_size" id="qr_size">
              <?php require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/settings-functions.php';
              echo qr_size_options($qr_size); ?>
            </select>
            <p class="description"><?php esc_html_e('Choose QR code size for download. Smaller sizes are better for small labels, larger for posters.', 'nutrition-labels'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="short_code_length"><?php esc_html_e('Short Code Length', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <select name="short_code_length" id="short_code_length">
              <option value="4" <?php nutrition_selected($short_code_length, 4); ?>>4 characters (More unique)</option>
              <option value="5" <?php nutrition_selected($short_code_length, 5); ?>>5 characters (Default)</option>
              <option value="6" <?php nutrition_selected($short_code_length, 6); ?>>6 characters (More collisions)</option>
              <option value="7" <?php nutrition_selected($short_code_length, 7); ?>>7 characters (Less collisions)</option>
              <option value="8" <?php nutrition_selected($short_code_length, 8); ?>>8 characters (Very readable)</option>
            </select>
            <p class="description"><?php esc_html_e('Length affects uniqueness vs readability. Shorter codes may conflict more often.', 'nutrition-labels'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="character_set"><?php esc_html_e('Character Set', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <select name="character_set" id="character_set">
              <?php require_once NUTRITION_LABELS_PLUGIN_DIR . 'admin/settings-functions.php';
              echo character_set_options($character_set); ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php esc_html_e('Current URL Format', 'nutrition-labels'); ?></label>
          </th>
          <td>
            <code>/l/[short-code]</code>
            <p class="description">All nutrition labels use the <strong>/l/</strong> prefix followed by the short code.</p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php nutrition_submit_button('Save Settings', 'primary', 'submit-nutrition-settings'); ?>
  </form>

  <div class="nutrition-labels-info">
    <h3><?php esc_html_e('Information', 'nutrition-labels'); ?></h3>
    <ul>
      <li><strong>Current Entries:</strong> <?php
                                            $db = new NutritionLabels_DB_Extended();
                                            echo $db->count_all_entries(); ?> nutrition labels active
        ?></li>
      <li><strong>URL Format:</strong> <code><?php echo home_url('/l/[shortcode]'); ?></code></li>
      <li><strong>QR Code Default:</strong> <code><?php echo esc_html(get_option('qr_size', '500x500')); ?></code></li>
      <li><strong>Database Table:</strong> <code>wp_nutrition_short_urls</code></li>
    </ul>
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
</style>
