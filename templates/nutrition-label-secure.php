<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($nutrition_data['product_title']); ?> - Nutrition Label</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: white;
            color: black;
        }
        .nutrition-label {
            max-width: 400px;
            margin: 0 auto;
            border: 1px solid #333;
            padding: 20px;
        }
        .product-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
        .ingredients {
            margin-bottom: 20px;
            font-size: 12px;
        }
        .nutrition-table {
            width: 100%;
            border-collapse: collapse;
        }
        .nutrition-table td {
            padding: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        .nutrition-table .label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="nutrition-label">
        <div class="product-title"><?php echo esc_html($nutrition_data['product_title']); ?></div>
        
        <?php if (!empty($nutrition_data['ingredient_list'])): ?>
        <div class="ingredients">
            <strong>Ingredients:</strong><br>
            <?php echo $nutrition_data['ingredient_list']; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($nutrition_data['calories']) || !empty($nutrition_data['kilojoules']) || !empty($nutrition_data['carbohydrates']) || !empty($nutrition_data['sugar'])): ?>
        <table class="nutrition-table">
            <?php if (!empty($nutrition_data['calories'])): ?>
            <tr>
                <td class="label">Calories</td>
                <td><?php echo esc_html(number_format($nutrition_data['calories'])); ?> kcal</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['kilojoules'])): ?>
            <tr>
                <td class="label">Kilojoules</td>
                <td><?php echo esc_html(number_format($nutrition_data['kilojoules'])); ?> kJ</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['carbohydrates'])): ?>
            <tr>
                <td class="label">Carbohydrates</td>
                <td><?php echo esc_html(number_format($nutrition_data['carbohydrates'], 1)); ?> g</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['sugar'])): ?>
            <tr>
                <td class="label">Sugar</td>
                <td><?php echo esc_html(number_format($nutrition_data['sugar'], 1)); ?> g</td>
            </tr>
            <?php endif; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>