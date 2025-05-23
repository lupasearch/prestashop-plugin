# LupaSearch PrestaShop plugin

Upgrade your PrestaShop store with our plugin that transforms your search feature. It connects to a fast, smart LupaSearch solution, making it easy for customers to find what they need quickly.

## Add custom attributes to LupaSearch product feed

LupaSearch provides two hooks to allow other modules to extend product and variant data:

- `actionLupaSearchAddProductAttributes`
- `actionLupaSearchAddVariantAttributes`

These hooks let your module inject custom attributes into the LupaSearch feed, enhancing search and filtering capabilities.

### How to extend product or variant data

#### 1. Create or edit a PrestaShop module

Documentation: https://devdocs.prestashop-project.org/1.7/modules/creation/tutorial/

#### 2. Register LupaSearch hooks in your module’s `install()` method

Documentation: https://devdocs.prestashop-project.org/1.7/modules/concepts/hooks/#registration

```php
public function install()
{
    return parent::install() &&
        $this->registerHook('actionLupaSearchAddProductAttributes') &&
        $this->registerHook('actionLupaSearchAddVariantAttributes');
}
```

#### 3. Add custom attributes to the LupaSearch feed

Documentation: https://devdocs.prestashop-project.org/1.7/modules/concepts/hooks/#execution

Example: Add custom attributes to products

```php
public function hookActionLupaSearchAddProductAttributes($params)
{
    $productIds = $params['product_ids'] ?? [];
    $shopId = $params['shop_id'] ?? null;
    $langId = $params['language_id'] ?? null;

    $data = [];

    foreach ($productIds as $id) {
        $data[$id] = [
            'custom_label' => 'Extra info for product ' . $id,
            'rating' => rand(1, 5),
        ];
    }

    return $data;
}
```

Example: Add custom attributes to variants

```php
public function hookActionLupaSearchAddVariantAttributes($params)
{
    $productIds = $params['product_ids'] ?? [];
    $combinationIds = $params['combination_ids'] ?? [];
    $shopId = $params['shop_id'] ?? null;
    $langId = $params['language_id'] ?? null;

    $data = [
        'products' => [],
        'combinations' => [],
    ];

    // Add custom attributes for simple product variants
    foreach ($productIds as $id) {
        $data['products'][$id] = [
            'variant_type' => 'simple',
            'sku_group' => 'product_' . $id,
        ];
    }

    // Add custom attributes for combination-based variants
    foreach ($combinationIds as $id) {
        $data['combinations'][$id] = [
            'variant_type' => 'combination',
            'color_label' => 'Color for combo #' . $id,
            'limited_edition' => (bool) rand(0, 1),
        ];
    }

    return $data;
}
```

For detailed guidance on setting up, reach out to our support team (support@lupasearch.com) who are ready to assist you with the process.
