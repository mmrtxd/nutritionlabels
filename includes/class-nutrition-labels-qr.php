<?php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

/**
 * QR Code generation for nutrition labels (local, via endroid/qr-code)
 */
class NutritionLabels_QR
{
  /**
   * Generates a QR code PNG and returns the raw binary string.
   *
   * @return string|false  Raw PNG bytes, or false on failure.
   */
  public static function generate_qr_code(string $url): string|false
  {
    try {
      $writer = new PngWriter();
      $qrCode = new QrCode(
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: self::configured_size(),
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
      );
      return $writer->write($qrCode)->getString();
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Generates a QR code and returns it as a data URI (data:image/png;base64,...).
   *
   * @return string|false  Data URI, or false on failure.
   */
  public static function generate_qr_code_base64(string $url): string|false
  {
    $png = self::generate_qr_code($url);
    if ($png === false) {
      return false;
    }
    return 'data:image/png;base64,' . base64_encode($png);
  }

  /**
   * Streams a QR code PNG directly to the browser as a file download.
   * For use as a direct download endpoint (non-AJAX).
   */
  public static function download_qr_code(int $product_id, string $product_name): void
  {
    if (!current_user_can('edit_posts')) {
      wp_die('Unauthorized');
    }

    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product') {
      wp_die('Product not found or invalid');
    }

    $short_url = NutritionLabels_URL::get_short_url($product_id);
    if (!$short_url) {
      wp_die('Unable to generate short URL for QR code');
    }

    $png = self::generate_qr_code($short_url);
    if ($png === false) {
      wp_die('Error generating QR code.');
    }

    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($product_name) . '-nutrition-qr.png"');
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');

    echo $png;
    exit;
  }

  /**
   * Returns the configured QR size in pixels, parsed from the qr_size option (e.g. "500x500").
   */
  private static function configured_size(): int
  {
    $size_str = get_option('qr_size', '500x500');
    $parts    = explode('x', $size_str);
    $size     = (int) ($parts[0] ?? 500);
    return $size > 0 ? $size : 500;
  }
}
