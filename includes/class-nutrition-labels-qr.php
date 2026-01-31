<?php

/**
 * QR Code generation for nutrition labels
 */

class NutritionLabels_QR
{

  public static function generate_qr_code($url)
  {
    // Use QR Server API - free and reliable
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&format=png&data=" . urlencode($url);

    $response = wp_remote_get($qr_url, array(
      'timeout' => 30,
      'headers' => array(
        'User-Agent' => 'Nutrition Labels Plugin'
      )
    ));

    if (is_wp_error($response)) {
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $response_code = wp_remote_retrieve_response_code($response);

    if ($response_code !== 200 || empty($body)) {
      return false;
    }

    return $body;
  }

  public static function generate_qr_code_base64($url)
  {
    $qr_data = self::generate_qr_code($url);

    if (!$qr_data) {
      return false;
    }

    return 'data:image/png;base64,' . base64_encode($qr_data);
  }

  public static function download_qr_code($product_id, $product_name)
  {
    // Security checks
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

    // Get or create the short URL
    $short_url = NutritionLabels_URL::get_short_url($product_id);

    if (!$short_url) {
      wp_die('Unable to generate short URL for QR code');
    }

    $qr_data = self::generate_qr_code($short_url);

    if (!$qr_data) {
      wp_die('Error generating QR code. Please try again later.');
    }

    // Security headers
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($product_name) . '-nutrition-qr.png"');
    header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
    header('X-Content-Type-Options: nosniff');

    echo $qr_data;
    exit;
  }
}
