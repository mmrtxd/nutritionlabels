<?php

enum IngredientType: string implements \JsonSerializable
{
  case Text = 'text';
  case Code = 'code';
  case Nil  = 'nil';

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

  // German display names: [group][key] => label
  private static array $labels = [
    'ingredients' => [
      'grapes'        => 'Trauben',
      'organicgrapes' => 'Bio-Trauben',
      'sacharose'     => 'Saccharose',
      'gconcentrate'  => 'Traubenkonzentrat',
    ],
    'conservants' => [
      'sulfur'      => 'Schwefeldioxid',
      'potbi'       => 'Kaliumhydrogensulfit',
      'potmetabi'   => 'Kaliumdisulfit',
      'potsorbate'  => 'Kaliumsorbat',
      'lysozyme'    => 'Lysozym',
      'ascorbic'    => 'Ascorbinsäure',
      'dmdc'        => 'Dimethyldicarbonat',
    ],
    'regulators' => [
      'tacid'    => 'Weinsäure',
      'macid'    => 'Äpfelsäure',
      'lacid'    => 'Milchsäure',
      'csulfate' => 'Calciumsulfat',
      'rcitric'  => 'Citronensäure',
    ],
    'stabilizers' => [
      'citric'       => 'Citronensäure',
      'metawine'     => 'Metaweinsäure',
      'gumarabic'    => 'Gummi arabicum',
      'yeastprotein' => 'Hefemannoproteine',
      'carboexy'     => 'Carboxymethylcellulose',
      'potpoly'      => 'Kaliumpolyaspartat',
      'fumar'        => 'Fumarsäure',
    ],
    'gases' => [
      'argon'     => 'Argon',
      'nitrogen'  => 'Stickstoff',
      'carbon'    => 'Kohlendioxid',
      'schutzatm' => 'unter Schutzatmosphäre abgepackt',
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
   * Returns the German display name for an ingredient key.
   */
  public static function getLabel(string $group, string $key): string
  {
    return self::$labels[$group][$key] ?? $key;
  }

  /**
   * Returns the EU E-number for an ingredient key, or empty string if none.
   */
  public static function getENumber(string $group, string $key): string
  {
    return self::$enumbers[$group][$key] ?? '';
  }

  // Group headings shown on the e-label (empty string = no heading, items listed inline)
  private static array $groupHeadings = [
    'ingredients' => '',
    'conservants' => 'Konservierungsstoffe',
    'regulators'  => 'Säureregulatoren',
    'stabilizers' => 'Stabilisatoren',
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
          $items[] = self::getLabel($group, $key);
        }
      }

      if (empty($items)) continue;

      $heading = self::$groupHeadings[$group] ?? '';
      $segments[] = $heading !== ''
        ? $heading . ': ' . implode(', ', $items)
        : implode(', ', $items);
    }

    return implode(', ', $segments);
  }
}
