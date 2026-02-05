# LupaSearch PrestaShop plugin

Upgrade your PrestaShop store with our plugin that transforms your search feature. It connects to a fast, smart LupaSearch solution, making it easy for customers to find what they need quickly.

## Requirements

- **PHP**: >=7.1
- **PrestaShop**: compatible with PrestaShop 1.7.x - 9.x

## Installation

### Installation steps

1. Download the [latest plugin version](https://github.com/lupasearch/prestashop-plugin/releases/download/latest/lupasearch.zip).
2. Log in to your PrestaShop back office.
3. Navigate to **Modules → Module Manager → Upload a module**.
4. Upload the downloaded `lupasearch.zip` file.
5. Follow the on-screen instructions to complete the installation.

### Configuration

1. Go to **Modules → Module Manager → LupaSearch integration → Configure**.
2. Enter the configuration values from your LupaSearch dashboard into the **UI Plugin Configuration Key** and **Product Index ID** fields, then click **Save**.
3. Activate the widget by setting **Enable Widget** to **Yes**.

Below is an example of how the configuration screen looks after setting up the LupaSearch plugin:

![LupaSearch Configuration Example](https://storage.googleapis.com/lupa-example-images/platforms/prestashop/plugin-configuration-sample.png)

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
