# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin ("Nutrition Labels" v1.4.1) that adds product nutrition label management with QR code generation and short URL routing. Designed for WooCommerce or any WordPress site with "product" post types. Labels are rendered in German.

## Development & Deployment

- **No build step required** — PHP files are directly executable by WordPress
- **Tailwind CSS** is pre-compiled to `templates/style.css` from `templates/input.css` (Tailwind v4.1.18)
- **No test suite, linter, or package manager** — no `package.json` or `composer.json`
- **Deploy** by copying the plugin folder to `wp-content/plugins/` and activating via WP admin
- **Requires:** WordPress 5.0+, PHP 8.4+, MySQL 5.6+

## Architecture

**Entry point:** `nutrition-labels.php` — registers activation/deactivation hooks, runs DB migrations, bootstraps admin and URL rewriting.

**Core classes in `includes/`:**

- `class-nutrition-labels-db-extended.php` — CRUD for the `wp_nutrition_short_urls` table, shortcode generation with collision detection, search/pagination, CSV export queries
- `class-nutrition-labels-url.php` — WordPress rewrite rules for short URLs (e.g., `/l/abc12`), front-end route handling, template loading, WooCommerce product page button (`render_product_button()` on `woocommerce_single_product_summary` priority 35)
- `class-nutrition-labels-settings.php` — `NutritionLabels_Settings`: single source for QR option names (`nutrition_labels_qr_*`), defaults, allowed values, getters, and the 1.3.0 legacy-option migration. Always read QR settings through it, never via raw `get_option()`.
- `class-nutrition-labels-qr.php` — local QR code generation via `endroid/qr-code` (bundled in `vendor/`, needs PHP 8.4)

**Admin UI in `admin/`:**

- `class-nutrition-labels-admin-extended.php` — admin menu registration, settings, AJAX handlers (delete/flush rules/QR download), CSV export. Note: `elabel_export_csv()` runs on every `admin_init` (incl. front-end admin-ajax.php) — it must return early unless it's an actual export request.
- `working-metabox.php` — product editor meta box for nutrition data entry, save hooks
- `nutrition-db-management.php` — database management page with search and bulk actions
- `nutrition-settings-page-simple.php` — settings page (QR size/format/error correction, subdomain, uninstall behaviour). URL prefix, shortcode length and charset are constants, not settings.

**Frontend:** `templates/nutrition-label-secure.php` — public-facing nutrition label rendered with Tailwind CSS.

**Admin JS:** `assets/js/admin.js` — QR download handler, form validation. Strings come from `nutritionLabels.i18n` (localized in the metabox).

## Database

Single custom table `wp_nutrition_short_urls` with columns: `id`, `product_id` (unique FK to posts), `url_prefix`, `short_code` (unique, NULL until an ingredient is selected — never `''`), `product_title` (copy of the post title, kept in sync via `post_updated`/`before_delete_post`; only used as fallback once the product is deleted — labels outlive their product), nutrition fields (`ingredients`, `calories`, `kilojoules`, `carbohydrates`, `sugar`), and timestamps.

## Security Patterns

All forms and AJAX use WordPress nonce verification, capability checks (`manage_options` for settings, `NutritionLabels_Admin_Extended::db_management_capability()` — `manage_woocommerce` with WooCommerce, else `manage_options` — for Database Management/delete/CSV, `edit_posts`), input sanitization (`sanitize_text_field`, `absint`, `floatval`), and output escaping (`esc_html`, `esc_attr`, `esc_url`). Follow these patterns when adding new endpoints.
