<?php

/**
 * Settings page for database management
 */

// Initialize
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$per_page = 50;

// Get entries
$db = new NutritionLabels_DB_Extended();
$entries = !empty($search) ? $db->search_entries($search, $per_page, $page) : $db->get_all_entries($per_page, $page);
$total = !empty($search) ? $db->count_search_results($search) : $db->count_all_entries();
?>

<div class="wrap">
  <h1><?php esc_html_e('Nutrition Labels - Database Management', 'nutrition-labels'); ?></h1>

  <div class="nutrition-labels-toolbar">
    <form method="get" action="">
      <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search product name or short code...', 'nutrition-labels'); ?>">
      <input type="submit" value="<?php esc_attr_e('Search', 'nutrition-labels'); ?>" class="button">
    </form>

    <?php if (!empty($search)): ?>
      <a href="?paged=1" class="button"><?php esc_html_e('Clear Search', 'nutrition-labels'); ?></a>
    <?php endif; ?>
  </div>

  <?php if (empty($entries)): ?>
    <div class="notice notice-warning">
      <p><?php esc_html_e('No nutrition label entries found.', 'nutrition-labels'); ?></p>
    </div>
  <?php else: ?>
    <div class="nutrition-labels-table-wrapper">
      <p><?php printf(esc_html__('Showing %1$d of %2$d entries', 'nutrition-labels'), count($entries), $total); ?></p>

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
              <th><?php esc_html_e('Prefix', 'nutrition-labels'); ?></th>
              <th><?php esc_html_e('Short Code', 'nutrition-labels'); ?></th>
              <th><?php esc_html_e('Created', 'nutrition-labels'); ?></th>
              <th class="column-actions"><?php esc_html_e('Actions', 'nutrition-labels'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
              <tr>
                <td class="check-column">
                  <input id="cb-select-<?php echo $entry->product_id; ?>" type="checkbox" name="product_ids[]" value="<?php echo $entry->product_id; ?>">
                  <label class="screen-reader-text" for="cb-select-<?php echo $entry->product_id; ?>"><?php esc_html_e('Select', 'nutrition-labels'); ?></label>
                </td>
                <td>
                  <strong><?php echo esc_html(get_the_title($entry->product_id)); ?></strong>
                  <br>
                  <small>ID: <?php echo $entry->product_id; ?></small>
                </td>
                <td>
                  <code><?php echo esc_html($entry->url_prefix); ?></code>
                </td>
                <td>
                  <code>/<?php echo esc_html(get_option('url_prefix', 'l')) . '/' . esc_html($entry->short_code); ?></code>
                </td>
                <td>
                  <?php echo date('Y-m-d H:i', strtotime($entry->created_at)); ?>
                </td>
                <td>
                  <button type="button" class="button" onclick="viewNutritionLabel(<?php echo $entry->product_id; ?>)">
                    <?php esc_html_e('View Label', 'nutrition-labels'); ?>
                  </button>
                  <button type="button" class="button" onclick="deleteEntry(<?php echo $entry->product_id; ?>)">
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
            href="<?php echo wp_nonce_url(
                    admin_url('admin.php?page=nutrition_labels_db_management&export=csv'),
                    'nutrition_labels_export'
                  ); ?>"
            class="button">
            <?php esc_html_e('Export to CSV', 'nutrition-labels'); ?>
          </a>
        </div>
      </form>
    </div>

    <?php if ($total > $per_page): ?>
      <div class="tablenav">
        <?php
        $current_url = add_query_arg(array('search' => $search, 'paged' => $page));

        echo paginate_links(array(
          'base' => add_query_arg('paged', '%_%%', $current_url),
          'format' => '',
          'prev_text' => __('&laquo; Previous'),
          'next_text' => __('Next &raquo;'),
          'total' => $total,
          'current' => $page,
          'per_page' => $per_page
        ));
        ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <script>
    // Define ajaxurl if not already defined
    if (typeof ajaxurl === 'undefined') {
      var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }

    function toggleAllCheckboxes(source) {
      checkboxes = document.getElementsByName('product_ids[]');
      for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
      }
    }

    function viewNutritionLabel(productId) {
      // Get the short code from the table
      var shortCode = '';
      jQuery('input[name="product_ids[]"]').each(function() {
        if (this.value == productId) {
          var row = jQuery(this).closest('tr');
          shortCode = row.find('td:nth-child(3) code').text().replace('/l/', '').trim();
        }
      });

      if (!shortCode) {
        shortCode = prompt('Enter short code for product ID ' + productId + ':');
        if (!shortCode) return;
      }

      window.open('<?php echo home_url('/l/'); ?>' + shortCode);
    }

    function deleteEntry(productId) {
      if (confirm('Delete nutrition label entry?\n\nProduct will NOT be deleted - only the nutrition label data will be removed.\n\nThis cannot be undone.')) {
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
            alert('Error: Could not delete entry');
            // Still reload to show current state
            location.reload();
          }
        });
      }
    }

    function viewNutritionLabel(productId) {
      // Get the short code from the current row
      var row = document.querySelector('tr:has(input[value="' + productId + '"])');
      var shortCodeCell = row.querySelector('td:nth-child(4) code');
      var shortCode = shortCodeCell.textContent.replace('/l/', '');
      window.open('<?php echo home_url('/l/'); ?>' + shortCode);
    }



    jQuery(document).ready(function($) {
      // Handle bulk delete button
      $('#bulk_delete').click(function() {
        var selectedIds = $('input[name="product_ids[]"]:checked').map(function() {
          return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
          alert('Please select at least one entry to delete');
          return;
        }

        if (confirm('Delete ' + selectedIds.length + ' nutrition label entries?\n\nProducts will NOT be deleted - only the nutrition label data will be removed.\n\nThis cannot be undone.')) {
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
              alert('Error: Could not delete entries');
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
  }
</style>
