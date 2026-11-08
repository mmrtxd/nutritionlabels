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

if (!current_user_can('manage_options')) {
  wp_die(__('You do not have permission to access this page.', 'nutrition-labels'));
}

/**
 * Settings page for database management
 */

// Initialize
$search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$per_page = 50;

// Get entries
$db = new NutritionLabels_DB_Extended();
$entries = !empty($search) ? $db->search_entries($search, $per_page, $page) : $db->get_all_entries($per_page, $page);
$total = (int) (!empty($search) ? $db->count_search_results($search) : $db->count_all_entries());
$base_url = admin_url('admin.php?page=nutrition_labels_db_management');
?>

<div class="wrap">
  <h1><?php esc_html_e('Nutrition Labels - Database Management', 'nutrition-labels'); ?></h1>

  <div class="nutrition-labels-toolbar">
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
      <input type="hidden" name="page" value="nutrition_labels_db_management">
      <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search product name or short code...', 'nutrition-labels'); ?>">
      <input type="submit" value="<?php esc_attr_e('Search', 'nutrition-labels'); ?>" class="button">
    </form>

    <?php if (!empty($search)): ?>
      <a href="<?php echo esc_url($base_url); ?>" class="button"><?php esc_html_e('Clear Search', 'nutrition-labels'); ?></a>
    <?php endif; ?>
  </div>

  <?php if (empty($entries)): ?>
    <div class="notice notice-warning">
      <p><?php esc_html_e('No nutrition label entries found.', 'nutrition-labels'); ?></p>
    </div>
  <?php else: ?>
    <div class="nutrition-labels-table-wrapper">
      <p><?php printf(esc_html__('Showing %1$d of %2$d entries', 'nutrition-labels'), count($entries), (int) $total); ?></p>

      <form method="post" action="">
        <?php wp_nonce_field('nutrition_delete', '_wpnonce'); ?>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <td class="manage-column column-cb check-column">
                <input type="checkbox" id="cb-select-all-1" onclick="toggleAllCheckboxes(this)">
                <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select All', 'nutrition-labels'); ?></label>
              </td>
              <th class="manage-column column-primary"><?php esc_html_e('Product', 'nutrition-labels'); ?></th>
              <th><?php esc_html_e('Short Code', 'nutrition-labels'); ?></th>
              <th><?php esc_html_e('Created', 'nutrition-labels'); ?></th>
              <th class="column-actions"><?php esc_html_e('Actions', 'nutrition-labels'); ?></th>
              <th class="export-action"><?php esc_html_e('Export Codes', 'nutrition-labels'); ?></th>
              <th class="delete-action">&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
              <?php
              $pid       = absint($entry->product_id);
              $title     = get_the_title($pid);
              $orphaned  = !get_post($pid);
              $label_url = NutritionLabels_URL::get_short_url($pid) ?: '';
              ?>
              <tr>
                <td class="check-column">
                  <input id="cb-select-<?php echo esc_attr($pid); ?>" type="checkbox" name="product_ids[]" value="<?php echo esc_attr($pid); ?>">
                  <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($pid); ?>"><?php esc_html_e('Select', 'nutrition-labels'); ?></label>
                </td>
                <td>
                  <?php if ($orphaned): ?>
                    <strong><em><?php esc_html_e('(product deleted)', 'nutrition-labels'); ?></em></strong>
                  <?php else: ?>
                    <strong><?php echo esc_html($title); ?></strong>
                  <?php endif; ?>
                  <br>
                  <small>ID: <?php echo esc_html($pid); ?></small>
                </td>
                <td>
                  <code>/<?php echo esc_html(NUTRITION_LABELS_URL_PREFIX) . '/' . esc_html($entry->short_code); ?></code>
                </td>
                <td>
                  <?php echo esc_html(date('Y-m-d H:i', strtotime($entry->created_at))); ?>
                </td>
                <td class="column-actions">
                  <?php if ($label_url !== ''): ?>
                    <a class="button" href="<?php echo esc_url($label_url); ?>" target="_blank" rel="noopener noreferrer">
                      <?php esc_html_e('View Label', 'nutrition-labels'); ?>
                    </a>
                  <?php endif; ?>
                  <?php if ($label_url !== '' && !$orphaned): ?>
                    <button type="button" class="button" onclick="downloadQrCode(<?php echo (int) $pid; ?>, this)">
                      <?php esc_html_e('Download QR', 'nutrition-labels'); ?>
                    </button>
                  <?php endif; ?>
                </td>
                <td class="export-action">
                  <select onchange="exportQrCode(<?php echo (int) $pid; ?>, this)" <?php disabled($label_url === '' || $orphaned); ?>>
                    <option value="">— <?php esc_html_e('Select', 'nutrition-labels'); ?> —</option>
                    <?php foreach (NutritionLabels_URL::get_lang_names() as $code => $name): ?>
                      <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="delete-action">
                  <button type="button" class="button" onclick="deleteEntry(<?php echo (int) $pid; ?>)">
                    <?php esc_html_e('Delete', 'nutrition-labels'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="nutrition-labels-bulk-actions">
          <button type="button" id="bulk_delete" class="button button-primary"><?php esc_html_e('Delete Selected', 'nutrition-labels'); ?></button>
          <a
            href="<?php echo esc_url(wp_nonce_url(
                    admin_url('admin.php?page=nutrition_labels_db_management&export=csv'),
                    'nutrition_labels_export'
                  )); ?>"
            class="button">
            <?php esc_html_e('Export to CSV', 'nutrition-labels'); ?>
          </a>
        </div>
      </form>
    </div>

    <?php if ($total > $per_page): ?>
      <div class="tablenav">
        <?php
        $paged_base = add_query_arg(array_filter(array('search' => $search)), $base_url);

        echo wp_kses_post(paginate_links(array(
          'base'      => add_query_arg('paged', '%#%', $paged_base),
          'format'    => '',
          'prev_text' => __('&laquo; Previous', 'nutrition-labels'),
          'next_text' => __('Next &raquo;', 'nutrition-labels'),
          'total'     => (int) ceil($total / $per_page),
          'current'   => $page,
        )));
        ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <script>
    // Define ajaxurl if not already defined
    if (typeof ajaxurl === 'undefined') {
      var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    }

    function toggleAllCheckboxes(source) {
      var checkboxes = document.getElementsByName('product_ids[]');
      for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
      }
    }

    function deleteEntry(productId) {
      if (confirm(<?php echo wp_json_encode(__("Delete nutrition label entry?\n\nProduct will NOT be deleted - only the nutrition label data will be removed.\n\nThis cannot be undone.", 'nutrition-labels')); ?>)) {
        jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'nutrition_delete',
            product_ids: [productId],
            _wpnonce: jQuery('input[name="_wpnonce"]').val()
          },
          success: function(response) {
            if (response.success) {
              alert(response.message || response.data.message);
              location.reload();
            } else {
              alert('Error: ' + (response.data || response.message));
            }
          },
          error: function() {
            alert(<?php echo wp_json_encode(__('Error: Could not delete entry', 'nutrition-labels')); ?>);
            // Still reload to show current state
            location.reload();
          }
        });
      }
    }

    var nutritionQrNonce = '<?php echo esc_js(wp_create_nonce('nutrition_qr_download')); ?>';

    function downloadQrCode(productId, button, langCode) {
      langCode = langCode || '';
      var originalText = button ? button.textContent : '';
      if (button) {
        button.disabled = true;
        button.textContent = '<?php echo esc_js(esc_html__('Generating...', 'nutrition-labels')); ?>';
      }

      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'nutrition_qr_download',
          product_id: productId,
          lang_code: langCode,
          nonce: nutritionQrNonce
        },
        success: function(response) {
          if (response.success) {
            var link = document.createElement('a');
            link.href = response.data.url;
            link.download = response.data.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          } else {
            alert('<?php echo esc_js(esc_html__('Error:', 'nutrition-labels')); ?> ' + (response.data || '<?php echo esc_js(esc_html__('Failed to generate QR code', 'nutrition-labels')); ?>'));
          }
        },
        error: function() {
          alert('<?php echo esc_js(esc_html__('Error: Could not generate QR code', 'nutrition-labels')); ?>');
        },
        complete: function() {
          if (button) {
            button.disabled = false;
            button.textContent = originalText;
          }
        }
      });
    }

    function exportQrCode(productId, select) {
      var langCode = select.value;
      if (!langCode) return;
      select.value = '';
      downloadQrCode(productId, null, langCode);
    }

    jQuery(document).ready(function($) {
      // Handle bulk delete button
      $('#bulk_delete').click(function() {
        var selectedIds = $('input[name="product_ids[]"]:checked').map(function() {
          return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
          alert(<?php echo wp_json_encode(__('Please select at least one entry to delete', 'nutrition-labels')); ?>);
          return;
        }

        /* translators: %d: number of selected entries */
        var confirmMsg = <?php echo wp_json_encode(__("Delete %d nutrition label entries?\n\nProducts will NOT be deleted - only the nutrition label data will be removed.\n\nThis cannot be undone.", 'nutrition-labels')); ?>;
        if (confirm(confirmMsg.replace('%d', selectedIds.length))) {
          $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
              action: 'nutrition_delete',
              product_ids: selectedIds,
              _wpnonce: $('input[name="_wpnonce"]').val()
            },
            success: function(response) {
              if (response.success) {
                alert(response.message || response.data.message);
                location.reload();
              } else {
                alert('Error: ' + (response.data || response.message));
              }
            },
            error: function() {
              alert(<?php echo wp_json_encode(__('Error: Could not delete entries', 'nutrition-labels')); ?>);
              // Still reload to show current state
              location.reload();
            }
          });
        }
      });
    });
  </script>
</div>

<style>
  .nutrition-labels-toolbar {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd;
    border-radius: 4px;
  }

  .nutrition-labels-toolbar form {
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .nutrition-labels-toolbar input[type="text"] {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 200px;
  }

  .nutrition-labels-table-wrapper {
    background: #fff;
    padding: 15px;
    border: 1px solid #ccd;
    border-radius: 4px;
    margin-top: 20px;
  }

  .nutrition-labels-table-wrapper table code {
    background: #f9f9f9;
    padding: 2px 4px;
    border-radius: 3px;
  }

  .nutrition-labels-bulk-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  th.column-actions,
  td.column-actions {
    width: 230px;
  }

  th.export-action {
    width: 150px;
  }

  th.delete-action {
    width: 75px;
  }

  td.column-actions,
  td.export-action,
  td.delete-action {
    vertical-align: middle;
  }

  .nutrition-labels-actions button {
    margin-right: 5px;
    font-size: 12px;
  }

  .check-column input[type="checkbox"] {
    margin: 0;
  }

  .nutrition-labels-table-wrapper .wp-list-table th.check-column {
    width: 2.2em;
    padding: 8px 10px;
  }

  .nutrition-labels-table-wrapper .wp-list-table td.check-column {
    width: 2.2em;
    padding: 8px 10px;
    vertical-align: middle;
  }
</style>
