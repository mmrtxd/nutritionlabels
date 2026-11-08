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

/**
 * Single source of truth for the plugin's QR settings: option names,
 * defaults, allowed values, sanitizing and reading.
 */
class NutritionLabels_Settings
{
  public const QR_SIZE             = 'nutrition_labels_qr_size';
  public const QR_FORMAT           = 'nutrition_labels_qr_format';
  public const QR_ERROR_CORRECTION = 'nutrition_labels_qr_error_correction';

  /**
   * Pre-1.3.0 option names (unprefixed) → current names.
   * Only used by the 1.3.0 migration and uninstall.
   */
  public const LEGACY_NAMES = [
    'qr_size'             => self::QR_SIZE,
    'qr_format'           => self::QR_FORMAT,
    'qr_error_correction' => self::QR_ERROR_CORRECTION,
  ];

  private const DEFAULTS = [
    self::QR_SIZE             => '500x500',
    self::QR_FORMAT           => 'png',
    self::QR_ERROR_CORRECTION => 'low',
  ];

  private const ALLOWED = [
    self::QR_SIZE             => ['300x300', '500x500', '800x800'],
    self::QR_FORMAT           => ['png', 'svg'],
    self::QR_ERROR_CORRECTION => ['low', 'medium', 'quartile', 'high'],
  ];

  public static function default(string $option): string
  {
    return self::DEFAULTS[$option];
  }

  public static function is_valid(string $option, mixed $value): bool
  {
    return is_string($value) && in_array($value, self::ALLOWED[$option] ?? [], true);
  }

  /** Returns $value if allowed for $option, otherwise the option's default. */
  public static function sanitize(string $option, mixed $value): string
  {
    return self::is_valid($option, $value) ? $value : self::default($option);
  }

  /** Reads an option; invalid or missing values fall back to the default. */
  public static function get(string $option): string
  {
    return self::sanitize($option, get_option($option, self::default($option)));
  }

  public static function qr_size(): string
  {
    return self::get(self::QR_SIZE);
  }

  public static function qr_format(): string
  {
    return self::get(self::QR_FORMAT);
  }

  public static function qr_error_correction(): string
  {
    return self::get(self::QR_ERROR_CORRECTION);
  }

  /**
   * 1.3.0 migration: move the unprefixed options to their prefixed names.
   *
   * - A legacy value is copied only if it is valid for us and the new option
   *   doesn't exist yet (never overwrites a value saved under the new name).
   * - The legacy option is deleted only if its value is one of ours: generic
   *   names like "qr_size" may belong to another plugin, whose data we must
   *   not touch.
   * Idempotent — safe to run more than once.
   */
  public static function migrate_legacy_options(): void
  {
    foreach (self::LEGACY_NAMES as $legacy => $current) {
      $value = get_option($legacy, false);
      if ($value === false || !self::is_valid($current, $value)) {
        continue; // Absent, or not ours — leave it alone.
      }

      if (get_option($current, false) === false) {
        add_option($current, $value);
      }

      delete_option($legacy);
    }
  }
}
