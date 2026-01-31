<?php
/**
 * FIXED VERSION: Secure QR code generation with fallbacks
 */

class NutritionLabels_QR {
    
    public static function generate_qr_code($url) {
        // Primary: QR Server API
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&format=png&data=" . urlencode($url);
        
        $response = wp_remote_get($qr_url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'Nutrition Labels Plugin'
            )
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code === 200 && !empty($body)) {
                return $body;
            }
        }
        
        // Fallback 1: Alternative QR service
        $fallback_url = "https://chart.googleapis.com/chart?chs=500x500&cht=qr&chl=" . urlencode($url);
        $response = wp_remote_get($fallback_url, array('timeout' => 10));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            if (!empty($body)) {
                return $body;
            }
        }
        
        // Fallback 2: Generate placeholder if all else fails
        return self::generate_placeholder_qr($url);
    }
    
    private static function generate_placeholder_qr($url) {
        // Generate a simple QR-like placeholder (not a real QR code)
        $img = imagecreatetruecolor(500, 500);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        
        imagefill($img, 0, 0, $bg);
        
        // Add border
        imagerectangle($img, 10, 10, 490, 490, $black);
        
        // Add text
        $text = "QR Code Error\n" . substr($url, -20); // Last 20 chars
        $font = 3;
        $text_width = imagefontwidth($font, $text);
        $text_height = imagefontheight($font);
        
        $x = (500 - $text_width) / 2;
        $y = (500 - $text_height) / 2;
        
        imagestring($img, $font, $x, $y, $black, $text);
        
        ob_start();
        imagepng($img);
        $data = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($img);
        return $data;
    }
    
    public static function generate_qr_code_base64($url) {
        $qr_data = self::generate_qr_code($url);
        
        if (!$qr_data) {
            return false;
        }
        
        return 'data:image/png;base64,' . base64_encode($qr_data);
    }
    
    public static function download_qr_code($product_id, $product_name) {
        // Enhanced security checks
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        
        if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
            wp_die('Invalid product ID');
        }
        
        // Validate product exists and is valid
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'product') {
            wp_die('Product not found or invalid');
        }
        
        // Rate limiting: max 5 QR codes per hour per user
        $user_id = get_current_user_id();
        $rate_key = 'qr_rate_limit_' . $user_id;
        $current_count = (int) get_transient($rate_key);
        
        if ($current_count >= 5) {
            wp_die('Rate limit exceeded. Please try again later.');
        }
        
        set_transient($rate_key, $current_count + 1, 3600); // 1 hour
        
        // Get or create the short URL
        $short_url = NutritionLabels_URL::get_short_url($product_id);
        
        if (!$short_url) {
            wp_die('Unable to generate short URL for QR code');
        }
        
        $qr_data = self::generate_qr_code($short_url);
        
        if (!$qr_data) {
            wp_die('Error generating QR code. Please try again later.');
        }
        
        // Enhanced security headers
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($product_name) . '-nutrition-qr.png"');
        header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
        header('X-Content-Type-Options: nosniff');
        
        echo $qr_data;
        exit;
    }
}

// Helper function for secure filename generation
function sanitize_file_name($filename) {
    $filename = sanitize_title($filename);
    $filename = preg_replace('/[^a-zA-Z0-9-_]/', '', $filename);
    return substr($filename, 0, 50); // Limit length
}