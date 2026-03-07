<?php

/**
 * URL handling for nutrition labels
 */

class NutritionLabels_URL
{

  private static $db;

  /**
   * Maps 2-letter ISO 639-1 codes to WordPress locale strings.
   * Only languages we have .mo files for are listed.
   */
  private static $lang_map = [
    'bg' => 'bg_BG',
    'cs' => 'cs_CZ',
    'da' => 'da_DK',
    'de' => 'de_DE',
    'el' => 'el_GR',
    'en' => 'en_US',
    'es' => 'es_ES',
    'et' => 'et_EE',
    'fi' => 'fi_FI',
    'fr' => 'fr_FR',
    'hr' => 'hr_HR',
    'hu' => 'hu_HU',
    'it' => 'it_IT',
    'lt' => 'lt_LT',
    'lv' => 'lv_LV',
    'nl' => 'nl_NL',
    'pl' => 'pl_PL',
    'pt' => 'pt_PT',
    'ro' => 'ro_RO',
    'sk' => 'sk_SK',
    'sl' => 'sl_SI',
    'sv' => 'sv_SE',
  ];

  /** Returns the code → locale map for validation use. */
  public static function get_lang_map(): array
  {
    return self::$lang_map;
  }

  /** Returns code → English display name pairs for UI dropdowns. */
  public static function get_lang_names(): array
  {
    return [
      'bg' => 'Bulgarian',
      'cs' => 'Czech',
      'da' => 'Danish',
      'de' => 'German',
      'el' => 'Greek',
      'en' => 'English',
      'es' => 'Spanish',
      'et' => 'Estonian',
      'fi' => 'Finnish',
      'fr' => 'French',
      'hr' => 'Croatian',
      'hu' => 'Hungarian',
      'it' => 'Italian',
      'lt' => 'Lithuanian',
      'lv' => 'Latvian',
      'nl' => 'Dutch',
      'pl' => 'Polish',
      'pt' => 'Portuguese',
      'ro' => 'Romanian',
      'sk' => 'Slovak',
      'sl' => 'Slovenian',
      'sv' => 'Swedish',
    ];
  }

  public static function init()
  {
    if (!function_exists('add_action') || !function_exists('add_filter')) {
      return;
    }

    self::$db = new NutritionLabels_DB_Extended();
    self::add_rewrite_rules();
    add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
    add_action('template_redirect', array(__CLASS__, 'handle_short_url'));
  }

  private static function get_db()
  {
    if (!self::$db) {
      self::$db = new NutritionLabels_DB_Extended();
    }
    return self::$db;
  }

  public static function add_rewrite_rules()
  {
    $prefix  = get_option('url_prefix', 'l');
    $charset = get_option('charset', 'alphanumeric');
    $preg    = '[a-zA-Z0-9]'; // default / only supported set

    add_rewrite_rule(
      '^' . preg_quote($prefix, '#') . '/(' . $preg . '{4,8})(?:-([a-z]{2}))?/?$',
      'index.php?nutrition_shortcode=$matches[1]&nutrition_lang=$matches[2]',
      'top'
    );
  }

  public static function add_query_vars($query_vars)
  {
    $query_vars[] = 'nutrition_shortcode';
    $query_vars[] = 'nutrition_lang';
    return $query_vars;
  }

  public static function handle_short_url()
  {
    $shortcode = get_query_var('nutrition_shortcode');
    $lang_raw  = get_query_var('nutrition_lang', '');

    if (is_admin() || empty($shortcode) || !ctype_alnum($shortcode)) {
      return;
    }

    // Resolve and validate the language code before any further processing.
    $locale = self::resolve_locale($lang_raw);

    $db         = self::get_db();
    $product_id = $db->get_product_id_by_shortcode($shortcode);

    if ($product_id) {
      self::display_nutrition_label($product_id, $locale);
    }
  }

  /**
   * Validates a raw lang string from the URL and returns a safe WP locale,
   * or an empty string if the code is unknown / invalid.
   *
   * Accepts only exactly 2 lowercase ASCII letters that exist in our map.
   */
  private static function resolve_locale(string $raw): string
  {
    if ($raw === '') {
      return '';
    }

    // Strict format check — no injection possible beyond this point
    if (!preg_match('/^[a-z]{2}$/', $raw)) {
      return '';
    }

    return self::$lang_map[$raw] ?? '';
  }

  private static function display_nutrition_label(int $product_id, string $locale = '')
  {
    // Switch locale so all __() calls use the requested language.
    //
    // WP 6.5+ uses WP_Translation_Controller with locale-keyed JIT loading.
    // Manually calling unload_textdomain() + load_textdomain() clears the
    // $l10n_unloaded guard, allowing the JIT registry (still pointing at the
    // site-default locale) to fire on the next __() call and overwrite our
    // manually loaded .mo with German.
    //
    // Fix: override get_locale() via the locale filter BEFORE unloading, then
    // let WP's JIT mechanism load the correct locale's .mo on the first __()
    // call. No manual load_textdomain() needed. We never call switch_to_locale()
    // because it registers reload callbacks that undo the switch.
    $locale_filter_fn = null;
    if ($locale !== '') {
      $locale_filter_fn = static function() use ($locale) { return $locale; };
      add_filter('locale', $locale_filter_fn, PHP_INT_MAX);
      unload_textdomain('nutrition-labels');
      // JIT will load the correct locale's .mo on the first __() call.
    }

    $db                   = self::get_db();
    $nutrition_table_data = $db->get_complete_nutrition_data($product_id);

    if (!$nutrition_table_data) {
      if ($locale_filter_fn !== null) {
        remove_filter('locale', $locale_filter_fn, PHP_INT_MAX);
      }
      wp_die(__('E-Label not found', 'nutrition-labels'));
    }

    $product_title = get_the_title($product_id);

    // toHtml() calls __() internally — must run after locale switch
    $ingredients_obj = $nutrition_table_data['ingredients'];
    if ($ingredients_obj instanceof NutritionLabelIngredientList) {
      $html_result         = $ingredients_obj->toHtml();
      $ingredient_display  = $html_result['html'];
      $ingredient_footnote = $html_result['footnote'];
    } else {
      $ingredient_display  = '';
      $ingredient_footnote = '';
    }

    $nutrition_data = array(
      'product_title'       => sanitize_text_field($product_title),
      'ingredient_list'     => $ingredient_display,
      'ingredient_footnote' => $ingredient_footnote,
      'calories'        => $nutrition_table_data['calories'],
      'kilojoules'      => $nutrition_table_data['kilojoules'],
      'carbohydrates'   => $nutrition_table_data['carbohydrates'],
      'sugar'           => $nutrition_table_data['sugar'],
    );

    $template = locate_template('nutrition-labels/nutrition-label-secure.php');
    if (empty($template)) {
      $template = NUTRITION_LABELS_PLUGIN_DIR . 'templates/nutrition-label-secure.php';
    }
    $template = apply_filters('nutrition_labels_template', $template, $product_id);

    include $template;
    exit;
  }

  /**
   * Returns the full short URL for a product, optionally with a language suffix.
   * e.g. https://example.com/l/abc12-de
   */
  public static function get_short_url(int $product_id, string $lang_code = ''): string|false
  {
    $db   = self::get_db();
    $data = $db->get_complete_nutrition_data($product_id);

    if (empty($data['short_code'])) {
      return false;
    }

    $prefix = get_option('url_prefix', 'l');
    $slug   = $data['short_code'];

    if ($lang_code !== '' && preg_match('/^[a-z]{2}$/', $lang_code) && isset(self::$lang_map[$lang_code])) {
      $slug .= '-' . $lang_code;
    }

    return home_url("/{$prefix}/{$slug}");
  }
}
