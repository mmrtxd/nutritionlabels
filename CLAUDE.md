# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin ("Nutrition Labels" v1.1.0) that adds product nutrition label management with QR code generation and short URL routing. Designed for WooCommerce or any WordPress site with "product" post types. Labels are rendered in German.

## Development & Deployment

- **No build step required** — PHP files are directly executable by WordPress
- **Tailwind CSS** is pre-compiled to `templates/style.css` from `templates/input.css` (Tailwind v4.1.18)
- **No test suite, linter, or package manager** — no `package.json` or `composer.json`
- **Deploy** by copying the plugin folder to `wp-content/plugins/` and activating via WP admin
- **Requires:** WordPress 5.0+, PHP 8.4+, MySQL 5.6+

## Architecture

**Entry point:** `nutrition-labels.php` — registers activation/deactivation hooks, runs DB migrations, bootstraps admin and URL rewriting.

**Three core classes in `includes/`:**

- `class-nutrition-labels-db-extended.php` — CRUD for the `wp_nutrition_short_urls` table, shortcode generation with collision detection, search/pagination, CSV export queries
- `class-nutrition-labels-url.php` — WordPress rewrite rules for short URLs (e.g., `/l/abc12`), front-end route handling, template loading
- `class-nutrition-labels-qr.php` — QR code generation via external QRServer API (`api.qrserver.com`), AJAX download handler

**Admin UI in `admin/`:**

- `class-nutrition-labels-admin-extended.php` — admin menu registration, settings, AJAX handlers (search/delete/flush rules), CSV export
- `working-metabox.php` — product editor meta box for nutrition data entry, save hooks
- `nutrition-db-management.php` — database management page with search and bulk actions
- `nutrition-settings-page.php` / `nutrition-settings-page-simple.php` — settings pages for QR size, shortcode length, character sets, URL prefix

**Frontend:** `templates/nutrition-label-secure.php` — public-facing nutrition label rendered with Tailwind CSS.

**Admin JS:** `assets/js/admin.js` — QR download handler, form validation, auto-save with 30-second debounce.

## Database

Single custom table `wp_nutrition_short_urls` with columns: `id`, `product_id` (unique FK to posts), `url_prefix`, `short_code` (unique), nutrition fields (`ingredients`, `calories`, `kilojoules`, `carbohydrates`, `sugar`), and timestamps.

## Security Patterns

All forms and AJAX use WordPress nonce verification, capability checks (`manage_options`, `edit_posts`), input sanitization (`sanitize_text_field`, `absint`, `floatval`), and output escaping (`esc_html`, `esc_attr`, `esc_url`). Follow these patterns when adding new endpoints.
