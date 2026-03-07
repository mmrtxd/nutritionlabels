<?php

enum IngredientType: string implements \JsonSerializable
{
  case Text    = 'text';
  case Code    = 'code';
  case Nil     = 'nil';
  case OrgText = 'orgtext';

  public function jsonSerialize(): mixed
  {
    return $this->value;
  }
}

class IngBaseIngredients implements \JsonSerializable
{
  public IngredientType $grapes        = IngredientType::Nil;
  public IngredientType $organicgrapes = IngredientType::Nil;
  public IngredientType $sacharose     = IngredientType::Nil;
  public IngredientType $gconcentrate  = IngredientType::Nil;

  public function jsonSerialize(): mixed
  {
    return get_object_vars($this);
  }
}

class IngConservants implements \JsonSerializable
{
  public IngredientType $sulfur = IngredientType::Nil; // sulfur 
  public IngredientType $potbi = IngredientType::Nil; // potassium bi sulfide 
  public IngredientType $potmetabi = IngredientType::Nil; // potassium meta bisulfide 
  public IngredientType $potsorbate = IngredientType::Nil; // potassium sorbate 
  public IngredientType $lysozyme = IngredientType::Nil; // lysozyme 
  public IngredientType $ascorbic = IngredientType::Nil; // ascorbic acid
  public IngredientType $dmdc = IngredientType::Nil; // dimethylcarbonate 

  public function jsonSerialize(): mixed
  {
    return get_object_vars($this);
  }
}


class IngAcidRegulators implements \JsonSerializable
{
  public IngredientType $tacid    = IngredientType::Nil; // tartaric acid
  public IngredientType $macid    = IngredientType::Nil; // malic acid
  public IngredientType $lacid    = IngredientType::Nil; // lactic acid
  public IngredientType $csulfate = IngredientType::Nil; // calcium sulfate
  public IngredientType $rcitric  = IngredientType::Nil; // citric acid (regulator)

  public function jsonSerialize(): mixed
  {
    return get_object_vars($this);
  }
}

class IngStabilizers implements \JsonSerializable
{
  public IngredientType $citric       = IngredientType::Nil; // citric acid
  public IngredientType $metawine     = IngredientType::Nil; // metatartaric acid
  public IngredientType $gumarabic    = IngredientType::Nil; // gum arabic
  public IngredientType $yeastprotein = IngredientType::Nil; // yeast mannoproteins
  public IngredientType $eggwhite     = IngredientType::Nil; // egg white (fining agent)
  public IngredientType $carboexy     = IngredientType::Nil; // carboxymethylcellulose
  public IngredientType $potpoly      = IngredientType::Nil; // potassium polyaspartate
  public IngredientType $fumar        = IngredientType::Nil; // fumaric acid

  public function jsonSerialize(): mixed
  {
    return get_object_vars($this);
  }
}

class IngGases implements \JsonSerializable
{
  public IngredientType $argon      = IngredientType::Nil;
  public IngredientType $nitrogen   = IngredientType::Nil;
  public IngredientType $carbon     = IngredientType::Nil;
  public IngredientType $schutzatm  = IngredientType::Nil; // packed under protective atmosphere

  public function jsonSerialize(): mixed
  {
    return get_object_vars($this);
  }
}

class NutritionLabelIngredientList implements \JsonSerializable
{
  public IngBaseIngredients $ingredients;
  public IngConservants $conservants;
  public IngAcidRegulators  $regulators;
  public IngStabilizers     $stabilizers;
  public IngGases           $gases;

  // Display names (English msgids): [group][key] => label
  private static array $labels = [
    'ingredients' => [
      'grapes'        => 'Grapes',
      'organicgrapes' => 'Organic Grapes',
      'sacharose'     => 'Sucrose',
      'gconcentrate'  => 'Grape Concentrate',
    ],
    'conservants' => [
      'sulfur'      => 'Sulfur Dioxide',
      'potbi'       => 'Potassium Bisulfite',
      'potmetabi'   => 'Potassium Metabisulfite',
      'potsorbate'  => 'Potassium Sorbate',
      'lysozyme'    => 'Lysozyme',
      'ascorbic'    => 'Ascorbic Acid',
      'dmdc'        => 'Dimethyl Dicarbonate',
    ],
    'regulators' => [
      'tacid'    => 'Tartaric Acid',
      'macid'    => 'Malic Acid',
      'lacid'    => 'Lactic Acid',
      'csulfate' => 'Calcium Sulfate',
      'rcitric'  => 'Citric Acid',
    ],
    'stabilizers' => [
      'citric'       => 'Citric Acid',
      'metawine'     => 'Metatartaric Acid',
      'gumarabic'    => 'Gum Arabic',
      'yeastprotein' => 'Yeast Mannoproteins',
      'eggwhite'     => 'Egg White',
      'carboexy'     => 'Carboxymethylcellulose',
      'potpoly'      => 'Potassium Polyaspartate',
      'fumar'        => 'Fumaric Acid',
    ],
    'gases' => [
      'argon'     => 'Argon',
      'nitrogen'  => 'Nitrogen',
      'carbon'    => 'Carbon Dioxide',
      'schutzatm' => 'Packaged under protective atmosphere',
    ],
  ];

  // E-number map: [group][key] => E-number string (empty string if none)
  private static array $enumbers = [
    'ingredients' => [
      'grapes'        => '',
      'organicgrapes' => '',
      'sacharose'     => '',
      'gconcentrate'  => '',
    ],
    'conservants' => [
      'sulfur'      => 'E220',
      'potbi'       => 'E228',
      'potmetabi'   => 'E224',
      'potsorbate'  => 'E202',
      'lysozyme'    => 'E1105',
      'ascorbic'    => 'E300',
      'dmdc'        => 'E242',
    ],
    'regulators' => [
      'tacid'    => 'E334',
      'macid'    => 'E296',
      'lacid'    => 'E270',
      'csulfate' => 'E516',
      'rcitric'  => 'E330',
    ],
    'stabilizers' => [
      'citric'       => 'E330',
      'metawine'     => 'E353',
      'gumarabic'    => 'E414',
      'yeastprotein' => '',
      'eggwhite'     => '',
      'carboexy'     => 'E466',
      'potpoly'      => '',
      'fumar'        => 'E297',
    ],
    'gases' => [
      'argon'     => 'E938',
      'nitrogen'  => 'E941',
      'carbon'    => 'E290',
      'schutzatm' => '',
    ],
  ];

  public function __construct()
  {
    $this->ingredients = new IngBaseIngredients();
    $this->conservants = new IngConservants();
    $this->regulators  = new IngAcidRegulators();
    $this->stabilizers = new IngStabilizers();
    $this->gases       = new IngGases();
  }

  /**
   * Hydrate from a JSON string (DB storage format).
   */
  public function hydrate(string $json): void
  {
    $data = json_decode($json, true);
    if (!is_array($data)) return;

    foreach ($data as $groupname => $values) {
      if (property_exists($this, $groupname) && is_array($values)) {
        foreach ($values as $key => $value) {
          if (property_exists($this->$groupname, $key)) {
            $this->$groupname->$key = IngredientType::tryFrom($value) ?? IngredientType::Nil;
          }
        }
      }
    }
  }

  /**
   * Hydrate from a nested POST array (already sanitized by caller).
   * Expected shape: ['ingredients' => ['grapes' => 'string', ...], 'gases' => [...], ...]
   */
  public function hydrateFromPost(array $data): void
  {
    foreach ($data as $groupname => $values) {
      if (property_exists($this, $groupname) && is_array($values)) {
        foreach ($values as $key => $value) {
          if (property_exists($this->$groupname, $key)) {
            $this->$groupname->$key = IngredientType::tryFrom($value) ?? IngredientType::Nil;
          }
        }
      }
    }
  }

  public function jsonSerialize(): mixed
  {
    return get_object_vars($this);
  }

  /**
   * Returns the translated display name for an ingredient key.
   */
  public static function getLabel(string $group, string $key): string
  {
    $msgid = self::$labels[$group][$key] ?? $key;
    return __($msgid, 'nutrition-labels');
  }

  /**
   * Returns the EU E-number for an ingredient key, or empty string if none.
   */
  public static function getENumber(string $group, string $key): string
  {
    return self::$enumbers[$group][$key] ?? '';
  }

  // Allergens (EU Reg. 1169/2011 Annex II) — bold display required regardless of display mode
  private static array $allergens = [
    'conservants' => ['sulfur', 'potbi', 'potmetabi', 'lysozyme'],
    'stabilizers' => ['eggwhite'],
  ];

  // Ingredients eligible for the OrgText (bio *) display mode
  private static array $organic_eligible = [
    'ingredients' => ['sacharose', 'gconcentrate'],
    'regulators'  => ['tacid'],
  ];

  public static function isAllergen(string $group, string $key): bool
  {
    return in_array($key, self::$allergens[$group] ?? [], true);
  }

  public static function isOrganicEligible(string $group, string $key): bool
  {
    return in_array($key, self::$organic_eligible[$group] ?? [], true);
  }

  // Group headings shown on the e-label (English msgids; empty string = no heading)
  private static array $groupHeadings = [
    'ingredients' => '',
    'conservants' => 'Preservatives',
    'regulators'  => 'Acid Regulators',
    'stabilizers' => 'Stabilizers',
    'gases'       => '',
  ];

  /**
   * Returns a display string for the e-label.
   * Groups with a heading emit "Heading: item1, item2".
   * Groups without a heading emit their items inline.
   * All segments are joined with ", ".
   */
  public function toDisplayString(): string
  {
    $segments = [];
    $groups   = ['ingredients', 'conservants', 'regulators', 'stabilizers', 'gases'];

    foreach ($groups as $group) {
      $groupObj = $this->$group;
      $items    = [];

      foreach (get_object_vars($groupObj) as $key => $type) {
        if ($type === IngredientType::Nil) continue;

        if ($type === IngredientType::Code) {
          $enumber = self::getENumber($group, $key);
          $items[] = $enumber !== '' ? $enumber : self::getLabel($group, $key);
        } else {
          // Text and OrgText both display as translated name
          $items[] = self::getLabel($group, $key);
        }
      }

      if (empty($items)) continue;

      $headingMsgid = self::$groupHeadings[$group] ?? '';
      $heading = $headingMsgid !== '' ? __($headingMsgid, 'nutrition-labels') : '';
      $segments[] = $heading !== ''
        ? $heading . ': ' . implode(', ', $items)
        : implode(', ', $items);
    }

    return implode(', ', $segments);
  }

  /**
   * Returns an HTML-safe ingredient string with allergens wrapped in <strong> and
   * organic-origin items marked with *.
   * Returns ['html' => string, 'footnote' => string].
   * Each text piece is individually escaped; structural markup (strong, commas) is not.
   */
  public function toHtml(): array
  {
    $segments    = [];
    $has_organic = false;
    $groups      = ['ingredients', 'conservants', 'regulators', 'stabilizers', 'gases'];

    foreach ($groups as $group) {
      $groupObj = $this->$group;
      $items    = [];

      foreach (get_object_vars($groupObj) as $key => $type) {
        if ($type === IngredientType::Nil) continue;

        if ($type === IngredientType::Code) {
          $enumber = self::getENumber($group, $key);
          $text    = esc_html($enumber !== '' ? $enumber : self::getLabel($group, $key));
        } else {
          $text = esc_html(self::getLabel($group, $key));
        }

        if (self::isAllergen($group, $key)) {
          $text = '<strong>' . $text . '</strong>';
        }

        if ($type === IngredientType::OrgText) {
          $text        .= '*';
          $has_organic  = true;
        }

        $items[] = $text;
      }

      if (empty($items)) continue;

      $headingMsgid = self::$groupHeadings[$group] ?? '';
      $heading      = $headingMsgid !== '' ? esc_html(__($headingMsgid, 'nutrition-labels')) : '';
      $segments[]   = $heading !== ''
        ? $heading . ': ' . implode(', ', $items)
        : implode(', ', $items);
    }

    $footnote = $has_organic
      ? esc_html__('* from organic farming', 'nutrition-labels')
      : '';

    return ['html' => implode(', ', $segments), 'footnote' => $footnote];
  }
}
