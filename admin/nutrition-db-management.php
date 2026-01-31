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
  <h1>Nutrition Labels - Database Management</h1>

  <div class="nutrition-labels-toolbar">
    <form method="get" action="">
      <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search product name or short code...">
      <input type="submit" value="Search" class="button">
    </form>

    <?php if (!empty($search)): ?>
      <a href="?paged=1" class="button">Clear Search</a>
    <?php endif; ?>
  </div>

  <?php if (empty($entries)): ?>
    <div class="notice notice-warning">
      <p>No nutrition label entries found.</p>
    </div>
  <?php else: ?>
    <div class="nutrition-labels-table-wrapper">
      <p>Showing <?php echo count($entries); ?> of <?php echo $total; ?> entries</p>

      <form method="post" action="">
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <td class="manage-column column-cb check-column">
                <label class="screen-reader-text" for="cb-select-all-1">
                  <input id="cb-select-all-1" type="checkbox">
                </label>
              </td>
              <th class="manage-column column-primary">Product</th>
              <th>Short Code</th>
              <th>Created</th>
              <th class="column-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
              <tr>
                <th scope="row" class="check-column">
                  <label class="screen-reader-text" for="cb-select-<?php echo $entry->product_id; ?>">
                    <input id="cb-select-<?php echo $entry->product_id; ?>" type="checkbox" name="product_ids[]" value="<?php echo $entry->product_id; ?>">
                  </label>
                </th>
                <td>
                  <strong><?php echo esc_html(get_the_title($entry->product_id)); ?></strong>
                  <br>
                  <small>ID: <?php echo $entry->product_id; ?></small>
                </td>
                <td>
                  <code>/l/<?php echo esc_html($entry->short_code); ?></code>
                </td>
                <td>
                  <?php echo date('Y-m-d H:i', strtotime($entry->created_at)); ?>
                </td>
                <td>
                  <button type="button" class="button" onclick="viewNutritionLabel(<?php echo $entry->product_id; ?>)">
                    View Label
                  </button>
                  <button type="button" class="button" onclick="deleteEntry(<?php echo $entry->product_id; ?>)">
                    Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="nutrition-labels-bulk-actions">
          <button type="submit" name="bulk_delete" class="button button-primary">Delete Selected</button>
          <button type="submit" name="export_csv" class="button">Export to CSV</button>
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
    function viewNutritionLabel(productId) {
      window.open('<?php echo home_url('/l/'); ?>' + prompt('Enter short code for this product:'));
    }

    function deleteEntry(productId) {
      if (confirm('Are you sure you want to delete this nutrition label entry? This cannot be undone.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = ajaxurl;

        var input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'action';
        input1.value = 'nutrition_delete';

        var input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'product_ids[]';
        input2.value = productId;

        var input3 = document.createElement('input');
        input3.type = 'hidden';
        input3.name = '_ajax_nonce';
        input3.value = '<?php echo NutritionLabels_Admin_Extended::get_delete_nonce(); ?>';

        form.appendChild(input1);
        form.appendChild(input2);
        form.appendChild(input3);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      }
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
</style>
