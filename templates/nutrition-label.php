<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($nutrition_data['product_title']); ?> - Nutrition Label</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .nutrition-label {
            width: 400px;
            font-family: Arial, sans-serif;
            border: 2px solid #000;
            margin: 0;
            padding: 0;
        }
        .label-header {
            background: #000;
            color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 15px 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-container {
            width: 60px;
            height: 40px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .main-content {
            background: #f8f8f8;
            padding: 20px;
        }
        .product-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }
        .ingredients {
            font-size: 12px;
            margin-bottom: 20px;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
        }
        .serving-size {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .nutrition-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .nutrition-table th {
            text-align: left;
            font-weight: bold;
            background: #f0f0f0;
            padding: 8px;
            border-bottom: 2px solid #000;
        }
        .nutrition-table td {
            padding: 8px;
            border-bottom: 1px solid #000;
        }
        .nutrient-row td:first-child {
            font-weight: bold;
            width: 50%;
        }
        .daily-value {
            font-weight: bold;
            font-size: 16px;
            color: #000;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 10px;
            background: #f0f0f0;
        }
        .separator {
            border-top: 3px solid #000;
            margin: 15px 0;
        }
    </style>
</head>
<body class="nutrition-label">
    <div class="label-header">
        <div class="logo-container">
            <svg width="40" height="25" viewBox="0 0 40 25" fill="none">
                <path d="M20 0C8.954 0 0 8.954 0 20v5c0 11.046 8.954 20 20 20s20-8.954 20-20V0zm-2 5c0-9.941-8.059-18-18-18S0-5.059 0-15 15v15h30v-30c0-9.941 8.059-18 18-18z" fill="#000"/>
            </svg>
        </div>
        <div>USDA</div>
        <div>Nutrition Facts</div>
    </div>
    
    <div class="main-content">
        <div class="product-title"><?php echo esc_html($nutrition_data['product_title']); ?></div>
        
        <?php if (!empty($nutrition_data['ingredient_list'])): ?>
        <div class="separator"></div>
        <div class="ingredients">
            <strong>Ingredients:</strong><br>
            <?php echo $nutrition_data['ingredient_list']; ?>
        </div>
        <?php endif; ?>
        
        <div class="separator"></div>
        
        <div class="serving-size">
            8 servings per container
            <br>
            <span class="daily-value">Serving size 1 cup (228g)</span>
        </div>
        
        <table class="nutrition-table">
            <tr class="nutrient-row">
                <td>Amount Per Serving</td>
                <td>% Daily Value*</td>
            </tr>
            <?php if (!empty($nutrition_data['calories'])): ?>
            <tr class="nutrient-row">
                <td>Calories</td>
                <td><?php echo esc_html(number_format($nutrition_data['calories'])); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['kilojoules'])): ?>
            <tr class="nutrient-row">
                <td>Kilojoules</td>
                <td><?php echo esc_html(number_format($nutrition_data['kilojoules'])); ?> kJ</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['carbohydrates'])): ?>
            <tr class="nutrient-row">
                <td>Total Carbohydrate</td>
                <td><?php echo esc_html(number_format($nutrition_data['carbohydrates'], 1)); ?> g</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['sugar'])): ?>
            <tr class="nutrient-row">
                <td>Total Sugars</td>
                <td><?php echo esc_html(number_format($nutrition_data['sugar'], 1)); ?> g</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($nutrition_data['protein'])): ?>
            <tr class="nutrient-row">
                <td>Protein</td>
                <td><?php echo esc_html(number_format($nutrition_data['protein'], 1)); ?> g</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="footer">
        * Percent Daily Values are based on a 2,000 calorie diet.
    </div>
</body>
</html>