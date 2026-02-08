jQuery(document).ready(function($) {
    // QR Code Download Handler
    $('#download_qr_code').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var productId = button.data('product-id');
        
        console.log('QR Code button clicked');
        console.log('Product ID:', productId);
        console.log('AJAX URL:', nutritionLabels.ajaxUrl);
        console.log('Nonce:', nutritionLabels.nonce);
        
        // Force refresh of nutrition data before generating QR code
        var buttonHtml = button.html();
        console.log('Current button HTML:', buttonHtml);
        
        if (!productId) {
            console.error('Product ID not found');
            alert('Product ID not found');
            return;
        }
        
        // Show loading state
        var originalText = button.text();
        button.prop('disabled', true).text('Generating QR code...');
        
        console.log('Generating QR code...');
        
        // AJAX request to generate QR code directly
        $.ajax({
            url: nutritionLabels.ajaxUrl,
            type: 'POST',
            data: {
                action: 'download_qr_code',
                product_id: productId,
                nonce: nutritionLabels.nonce
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                if (response.success) {
                    console.log('QR Code URL:', response.data.url);
                    console.log('QR Code Filename:', response.data.filename);
                    
                    // Create download link
                    var link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    console.error('QR Generation Error:', response.data);
                    alert('Error: ' + (response.data || 'Failed to generate QR code'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                alert('AJAX Error: ' + error);
            },
            complete: function() {
                // Restore button state
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Form validation (optional enhancement)
    $('form#post').on('submit', function() {
        var calories = $('input[name="nutrition_calories"]').val();
        var kilojoules = $('input[name="nutrition_kilojoules"]').val();
        
        // Basic validation
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
    
    // Auto-save functionality (optional)
    var autoSaveTimer;
    
    function triggerAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Only auto-save if not already saving
            if (!$('#save-post').prop('disabled')) {
                $('#save-post').click();
            }
        }, 30000); // 30 seconds
    }
    
    // Trigger auto-save on field changes
    $('input[name^="nutrition_"], #nutrition_ingredients').on('change', function() {
        triggerAutoSave();
    });
});