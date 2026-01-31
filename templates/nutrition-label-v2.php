<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition Label</title>
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
            <tr class="nutrient-row">
                <td>Calories</td>
                <td>150</td>
            </tr>
            <tr class="nutrient-row">
                <td>Total Fat</td>
                <td>8g</td>
            </tr>
            <tr class="nutrient-row">
                <td>Saturated Fat</td>
                <td>1g</td>
            </tr>
            <tr class="nutrient-row">
                <td>Trans Fat</td>
                <td>0g</td>
            </tr>
            <tr class="nutrient-row">
                <td>Cholesterol</td>
                <td>0mg</td>
            </tr>
            <tr class="nutrient-row">
                <td>Sodium</td>
                <td>10mg</td>
            </tr>
            <tr class="nutrient-row">
                <td>Total Carbohydrate</td>
                <td>27g</td>
            </tr>
            <tr class="nutrient-row">
                <td>Dietary Fiber</td>
                <td>4g</td>
            </tr>
            <tr class="nutrient-row">
                <td>Total Sugars</td>
                <td>12g</td>
            </tr>
            <tr class="nutrient-row">
                <td>Protein</td>
                <td>3g</td>
            </tr>
        </table>
        
        <div class="separator"></div>
        
        <table class="nutrition-table">
            <tr class="nutrient-row">
                <td>Vitamin D</td>
                <td>0mcg</td>
            </tr>
            <tr class="nutrient-row">
                <td>Calcium</td>
                <td>20mg</td>
            </tr>
            <tr class="nutrient-row">
                <td>Iron</td>
                <td>0.9mg</td>
            </tr>
            <tr class="nutrient-row">
                <td>Potassium</td>
                <td>260mg</td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        * Percent Daily Values are based on a 2,000 calorie diet.
    </div>
</body>
</html>