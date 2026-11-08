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

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

/**
 * QR Code generation for nutrition labels (local, via endroid/qr-code)
 */
class NutritionLabels_QR
{
  /**
   * Generates a QR code and returns the raw output string.
   * Format is 'png' (default) or 'svg', falling back to the qr_format option.
   *
   * @return string|false  Raw bytes (PNG) or SVG markup, or false on failure.
   */
  public static function generate_qr_code(string $url, ?string $format = null): string|false
  {
    if ($format === null) {
      $format = get_option('qr_format', 'png');
    }

    try {
      $writer = $format === 'svg' ? new SvgWriter() : new PngWriter();
      $qrCode = new QrCode(
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: self::configured_error_correction(),
        size: self::configured_size(),
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
      );
      return $writer->write($qrCode)->getString();
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * Generates a QR code and returns it as a data URI.
   * PNG → data:image/png;base64,…  SVG → data:image/svg+xml;base64,…
   *
   * @return string|false  Data URI, or false on failure.
   */
  public static function generate_qr_code_base64(string $url, ?string $format = null): string|false
  {
    if ($format === null) {
      $format = get_option('qr_format', 'png');
    }

    $data = self::generate_qr_code($url, $format);
    if ($data === false) {
      return false;
    }

    $mime = $format === 'svg' ? 'image/svg+xml' : 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode($data);
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

  /**
   * Returns the configured ErrorCorrectionLevel from the qr_error_correction option.
   */
  private static function configured_error_correction(): ErrorCorrectionLevel
  {
    $value = get_option('qr_error_correction', 'low');
    return ErrorCorrectionLevel::tryFrom((string) $value) ?? ErrorCorrectionLevel::Low;
  }
}
