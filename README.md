# Nutrition Labels for WordPress

Add nutrition label management, short URLs, and QR code generation to your WooCommerce or WordPress products.

## Description

Nutrition Labels is a WordPress plugin that allows you to store and display nutrition information for products. It generates memorable short URLs and QR codes that link to dedicated nutrition label pages for each product.

### Features

- **Product Nutrition Meta Box**: Add nutrition information (ingredients, calories, kJ, carbohydrates, sugar) directly from the product editor
- **Structured Ingredient List**: Select individual ingredients from predefined groups (base ingredients, preservatives, acid regulators, stabilisers, gases); allergens are automatically bolded per EU Reg. 1169/2011 Annex II
- **Organic Marks**: Eligible ingredients can be flagged as organic origin (`*`), with an automatic footnote
- **Short URLs**: Generate memorable shortcodes (e.g., `/l/abc12`) for easy sharing
- **Multilingual Labels**: Append a language suffix to the URL (e.g., `/l/abc12-en`) to serve the label in a different language
- **QR Code Generation**: Download QR codes (PNG or SVG) that link directly to a product's nutrition label
- **CSV Export**: Export all nutrition data to a CSV file
- **Database Management**: Search, view, and manage nutrition entries from the admin dashboard
- **Customizable Settings**: Configure QR code size, format, and error correction level
- **Clean Uninstall**: Optionally delete all plugin data when the plugin is removed

## Requirements

- WordPress 5.0 or higher
- PHP 8.4 or higher (required by endroid/qr-code)
- MySQL 5.6 or higher

## Installation

1. Upload the `nutrition-labels` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Nutrition Labels > Configuration** to adjust settings

## Usage

### Adding Nutrition Information to a Product

1. Edit any product in your WordPress admin
2. Scroll to the **Nutrition Information** meta box
3. Fill in the nutrition fields:
   - **Ingredients**: Select ingredients from the structured groups; choose display mode per ingredient (Text, E-number code, Organic, or None)
   - **Calories (kcal)**: Energy value in kilocalories
   - **Kilojoules (kJ)**: Energy value in kilojoules
   - **Carbohydrates (g)**: Total carbohydrate content
   - **Sugar (g)**: Sugar content
4. Save the product

A short URL will be automatically generated when at least one ingredient is set.

### Viewing a Product's Nutrition Label

Visit the short URL format: `https://yoursite.com/[prefix]/[shortcode]`

For example: `https://yoursite.com/l/abc12`

To request the label in a specific language, append a two-letter ISO 639-1 language code:

- `https://yoursite.com/l/abc12-en` — English
- `https://yoursite.com/l/abc12-de` — German

If no language suffix is given, the site default locale is used. Falls back to English for unsupported languages.

### Downloading QR Codes

1. Edit a product with nutrition information
2. In the Nutrition Information meta box, find the **Nutrition Label URL** field
3. Click **Download QR Code** to save a QR code image

QR codes are generated locally on your server using the [endroid/qr-code](https://github.com/endroid/qr-code) library. No data is sent to any external service.

## Admin Settings

Navigate to **Nutrition Labels** in the WordPress admin dashboard. Changing settings requires the `manage_options` capability (Administrator role).

### Configuration

Adjust the following settings under **Nutrition Labels > Configuration**:

| Setting | Description | Default |
|---------|-------------|---------|
| QR Code Size | Pixel dimensions of downloaded QR codes | 500×500 |
| QR Code Format | PNG (raster) or SVG (vector, recommended for print) | PNG |
| Error Correction | Module density vs. damage resilience trade-off | Low |
| Delete Data on Uninstall | Drop the database table and all records when the plugin is deleted | Off |

> **Warning:** Enabling "Delete Data on Uninstall" is irreversible. All nutrition label records will be permanently deleted when the plugin is removed from WordPress.

The URL prefix, shortcode length, and character set are deployment constants defined in `nutrition-labels.php`. Advanced users can override them before the plugin loads in `wp-config.php`:

```php
define('NUTRITION_LABELS_URL_PREFIX',       'n');   // changes /l/ to /n/
define('NUTRITION_LABELS_SHORTCODE_LENGTH', 6);
define('NUTRITION_LABELS_CHARACTER_SET',    'alphanumeric');
```

### Database Management

Under **Nutrition Labels > Database Management** you can:

- Search nutrition entries by product name or short code
- View all nutrition data in a table
- Delete nutrition entries
- Export all data to CSV

## Uninstalling

Deactivating the plugin preserves all data. To remove the plugin cleanly:

1. Go to **Nutrition Labels > Configuration** and enable **Delete Data on Uninstall**
2. Save the settings
3. Delete the plugin from the WordPress Plugins screen

If **Delete Data on Uninstall** is not enabled before deletion, the database table and all nutrition records are kept in the database. Plugin options (QR settings, DB version) are always removed on uninstall.

## Database Table

The plugin creates a single database table: `wp_nutrition_short_urls`

| Column | Type | Description |
|--------|------|-------------|
| id | MEDIUMINT(9) | Primary key |
| product_id | BIGINT(20) | WordPress product post ID |
| url_prefix | VARCHAR(10) | URL prefix (e.g., 'l') |
| short_code | VARCHAR(10) | Unique short URL code |
| ingredients | TEXT | Ingredient list (JSON) |
| calories | MEDIUMINT(5) | Calories value |
| kilojoules | MEDIUMINT(6) | Kilojoules value |
| carbohydrates | DECIMAL(6,2) | Carbohydrates in grams |
| sugar | DECIMAL(6,2) | Sugar in grams |
| created_at | DATETIME | Record creation time |
| updated_at | DATETIME | Last update time |

## User Permissions

| Action | Required Capability |
|--------|-------------------|
| Edit nutrition data on a product | `edit_posts` |
| Download QR codes | `edit_posts` |
| Access admin settings & database management | `manage_options` |
| Delete entries / CSV export | `manage_options` |

## Hooks and Filters

### Filter: `nutrition_labels_template`
Override the nutrition label template file.

```php
add_filter('nutrition_labels_template', 'my_custom_template');
function my_custom_template() {
    return '/path/to/your/custom-template.php';
}
```

### Action: `nutrition_labels_saved`
Runs after nutrition data is saved for a product.

```php
add_action('nutrition_labels_saved', 'my_callback', 10, 2);
function my_callback($product_id, $data) {
    // Do something after saving
}
```

## Frequently Asked Questions

**Do I need WooCommerce to use this plugin?**

No. This plugin works with any custom post type named "product". It integrates with WooCommerce products automatically if WooCommerce is installed.

**How are shortcodes generated?**

Shortcodes are randomly generated alphanumeric strings (A-Z, 0-9) with a configurable length (4-8 characters). The generator ensures uniqueness across all entries.

**Can I change the URL prefix?**

Yes, by defining the `NUTRITION_LABELS_URL_PREFIX` constant in `wp-config.php` before the plugin loads. Note: changing it will invalidate any existing printed QR codes.

**Where are QR codes generated?**

QR codes are generated locally on your server using the [endroid/qr-code](https://github.com/endroid/qr-code) library (MIT licence). No data is sent to any external service.

**Will my data be lost if I deactivate the plugin?**

No. Deactivating the plugin does not touch the database. Data is only deleted on uninstall if you have explicitly enabled the **Delete Data on Uninstall** setting before deleting the plugin.

## License

Copyright (c) 2026 - Markus Hammer - https://github.com/mmrtxd/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

## Dependencies

- [endroid/qr-code](https://github.com/endroid/qr-code) — MIT License

## Support

For issues and feature requests, please open a GitHub issue.
