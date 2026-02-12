<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo esc_html($nutrition_data['product_title']); ?> - Nutrition Label</title>
  <link rel="stylesheet" href="<?php echo plugins_url('nutrition-labels/assets/css/style.css');  ?>">
</head>

<body class="bg-stone-300 p-5">
  <div id="label" class="bg-white p-5 text-xl shadow-xl w-full">
    <h1 class="text-1xl font-bold uppercase tracking-wider m-2 p-6 pt-3 pb-0">
      <?php echo esc_html($nutrition_data['product_title']); ?>
    </h1>

    <?php if (!empty($nutrition_data['ingredient_list'])): ?>
      <h4 class="m-2 p-6 pb-0 text-sm font-medium tracking-widest uppercase">
        Inhaltsstoffe:
      </h4>

      <div id="elabel-ingredientslist" class="m-2 p-6 pt-0 pb-5">
        <p class="text-lg">
          <?php echo $nutrition_data['ingredient_list']; ?>
        </p>
      </div>
    <?php endif; ?>
    <div id="nutrtiontable" class="border outline-double m-2 p-1">
      <table class="border-2 p-0 border-black text-base w-full">
        <tr class="text-base bg-black font-bold text-white">
          <td class="p-5">Nährwertangaben</td>
          <td class="p-5 text-right">Pro 100ml</td>
        </tr>
        <tr class="border">
          <td class="p-5 align-top">
            Energie
          </td>
          <td class="p-5 text-right"><?php echo esc_html(number_format($nutrition_data['kilojoules'])); ?> kj<br><?php echo esc_html(number_format($nutrition_data['calories'])); ?></td>
        </tr>
        <tr class="border">
          <td class="p-5">
            Kohlenhydrate
          </td>
          <td class="p-5 text-right">
            <?php echo esc_html(number_format($nutrition_data['carbohydrates'], 1)); ?> g
          </td>
        </tr>
        <tr>
          <td class="p-5">Davon Zucker</td>
          <td class="p-5 text-right"><?php echo esc_html(number_format($nutrition_data['sugar'], 1)); ?> g</td>
        </tr>
      </table>
      <div id="negligible" class="text-sm p-4 tracking-widest uppercase text-center">
        <p>Kann geringfügige Mengen von Eiweiß und Salz enthalten.</p>
      </div>
    </div>
  </div>

</body>

</html>
