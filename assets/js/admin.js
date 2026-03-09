jQuery(document).ready(function($) {
    // QR Code Download Handler
    $('#download_qr_code').on('click', function(e) {
        e.preventDefault();

        var button    = $(this);
        var productId = button.data('product-id');

        if (!productId) {
            alert('Product ID not found');
            return;
        }

        var originalText = button.text();
        button.prop('disabled', true).text('Generating QR code...');

        $.ajax({
            url: nutritionLabels.ajaxUrl,
            type: 'POST',
            data: {
                action:     'nutrition_qr_download',
                product_id: productId,
                nonce:      nutritionLabels.nonce
                // no lang_code — defaults to site locale
            },
            success: function(response) {
                if (response.success) {
                    var link = document.createElement('a');
                    link.href     = response.data.url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert('Error: ' + (response.data || 'Failed to generate QR code'));
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX Error: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Form validation
    $('form#post').on('submit', function() {
        var calories   = $('input[name="nutrition_calories"]').val();
        var kilojoules = $('input[name="nutrition_kilojoules"]').val();

        if (calories && (isNaN(calories) || calories < 0)) {
            alert('Please enter a valid number for calories');
            $('input[name="nutrition_calories"]').focus();
            return false;
        }

        if (kilojoules && (isNaN(kilojoules) || kilojoules < 0)) {
            alert('Please enter a valid number for kilojoules');
            $('input[name="nutrition_kilojoules"]').focus();
            return false;
        }

        return true;
    });

    // Auto-save on field changes (30-second debounce)
    var autoSaveTimer;

    function triggerAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            if (!$('#save-post').prop('disabled')) {
                $('#save-post').click();
            }
        }, 30000);
    }

    $('input[name^="nutrition_"], #nutrition_ingredients').on('change', function() {
        triggerAutoSave();
    });
});
