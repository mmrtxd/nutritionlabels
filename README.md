# Nutrition Labels for WordPress

Add nutrition label management, short URLs, and QR code generation to your WooCommerce or WordPress products.

## Description

Nutrition Labels is a WordPress plugin that allows you to store and display nutrition information for products. It generates memorable short URLs and QR codes that link to dedicated nutrition label pages for each product.

### Features

- **Product Nutrition Meta Box**: Add nutrition information (ingredients, calories, kJ, carbohydrates, sugar) directly from the product editor
- **Short URLs**: Generate memorable shortcodes (e.g., `/l/abc12`) for easy sharing
- **QR Code Generation**: Download QR codes that link directly to a product's nutrition label
- **CSV Export**: Export all nutrition data to a CSV file
- **Database Management**: Search, view, and manage nutrition entries from the admin dashboard
- **Customizable Settings**: Configure QR code size, shortcode length, and character sets

## Installation

1. Upload the `nutrition-labels` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Nutrition Labels > Configuration** to adjust settings

## Usage

### Adding Nutrition Information to a Product

1. Edit any product in your WordPress admin
2. Scroll to the **Nutrition Information** meta box
3. Fill in the nutrition fields:
   - **Ingredient List**: Full text of ingredients
   - **Calories (kcal)**: Energy value in kilocalories
   - **Kilojoules (kJ)**: Energy value in kilojoules
   - **Carbohydrates (g)**: Total carbohydrate content
   - **Sugar (g)**: Sugar content
4. Save the product

A short URL will be automatically generated for the product.

### Viewing a Product's Nutrition Label

Visit the short URL format: `https://yoursite.com/[prefix]/[shortcode]`

For example: `https://yoursite.com/l/abc12`

### Downloading QR Codes

1. Edit a product with nutrition information
2. In the Nutrition Information meta box, find the **Nutrition Label URL** field
3. Click **Download QR Code** to save a QR code image

### Shortcodes

No shortcodes required. The plugin automatically generates short URLs for each product.

## Admin Settings

Navigate to **Nutrition Labels** in the WordPress admin dashboard.

### Configuration

Adjust the following settings under **Nutrition Labels > Configuration**:

| Setting | Description | Default |
|---------|-------------|---------|
| QR Code Size | Size of downloaded QR codes | 500x500 |
| Short Code Length | Characters in short URLs (4-8) | 5 |
| Character Set | Characters used in shortcodes | alphanumeric |
| URL Prefix | Base path for nutrition URLs | /l/ |

### Database Management

Under **Nutrition Labels > Database Management** you can:

- Search nutrition entries by product name or short code
- View all nutrition data in a table
- Delete nutrition entries
- Export all data to CSV

## Database Table

The plugin creates a single database table: `wp_nutrition_short_urls`

| Column | Type | Description |
|--------|------|-------------|
| id | MEDIUMINT(9) | Primary key |
| product_id | BIGINT(20) | WordPress product post ID |
| url_prefix | VARCHAR(10) | URL prefix (e.g., 'l') |
| short_code | VARCHAR(10) | Unique short URL code |
| ingredients | TEXT | Ingredient list |
| calories | MEDIUMINT(5) | Calories value |
| kilojoules | MEDIUMINT(6) | Kilojoules value |
| carbohydrates | DECIMAL(6,2) | Carbohydrates in grams |
| sugar | DECIMAL(6,2) | Sugar in grams |
| created_at | DATETIME | Record creation time |
| updated_at | DATETIME | Last update time |

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

Yes. Go to **Nutrition Labels > Configuration** and modify the URL Prefix setting. Note: Changing this will invalidate existing short URLs.

**Where are QR codes generated?**

QR codes are generated using the QRServer API. The QR code image is not stored locally but is created on-demand when downloaded.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## License

GPLv2 or later - see LICENSE file.

## Support

For issues and feature requests, please open a GitHub issue.
