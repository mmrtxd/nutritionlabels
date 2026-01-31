We want to create a wordpress plugin that extends woocommerce products by the following feature:

The website owner can specify a nutrition table for the product in the administration backend. 

The nutrition label contains:
[ingredient list] - this is a formatable text ----------
[calories] - integer for kcal display
[kilojoules] - integer value for kilojoules display
[carbohydrates] - float value - e.g. 0.8 to display carbohydrates
[sugar] - float value - e.g. 0.1 for residual sugar

These values need to be added to products.

For displaying, we need a shortened link that resides on the wordpress root - like: website.com/fhasj1 that renders a seperate page with this nutrition label. This page must not have any styling from the original wordpress site - we just need to render the product title there followed by ingredient list and a table displaying the calories, kilojoules, carbohydrates and sugar.

To access this page we want to generate a QR code which is used on the actual real life product label.
The site owner can download this qr code from the product page, and the generated file should have a proper naming to make it identifiable by product name.


