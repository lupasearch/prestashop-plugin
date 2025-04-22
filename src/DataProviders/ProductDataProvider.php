<?php
/**
 * @author LupaSearch
 * @copyright LupaSearch
 * @license MIT
 */

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\DataProviders;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LupaSearch\LupaSearchPlugin\QueryProvider;
use LupaSearch\LupaSearchPlugin\Utils\AttributeValidator;
use PrestaShop\PrestaShop\Adapter\Entity\Db;

class ProductDataProvider
{
    private $queryProvider;

    private $categoryDataProvider;

    public function __construct(QueryProvider $queryProvider = null, CategoryDataProvider $categoryDataProvider = null)
    {
        $this->queryProvider = $queryProvider ?? new QueryProvider();
        $this->categoryDataProvider = $categoryDataProvider ?? new CategoryDataProvider();
    }

    public function getFormattedProducts(int $page = 1, int $limit = 20): array
    {
        $context = \Context::getContext();
        $languageId = $context->language->id;
        $shopId = $context->shop->id;

        $offset = ($page - 1) * $limit;

        $products = Db::getInstance()->executeS(
            $this->queryProvider->getPaginatedProductsQuery($shopId, $languageId, $offset, $limit)
        );

        if (empty($products)) {
            return [];
        }

        $productIds = array_column($products, 'id_product');

        $categories = $this->getProductCategories($productIds, $shopId, $languageId);
        $images = $this->getProductImages($productIds, $shopId, $languageId);
        $brands = $this->getProductManufacturers($products, $shopId);
        $attributes = $this->getProductAttributes($productIds, $shopId, $languageId);
        $features = $this->getProductFeatures($productIds, $shopId, $languageId);
        $tags = $this->getProductTags($productIds, $languageId);
        $additionalAttributes = $this->getAdditionalProductAttributes($productIds, $shopId, $languageId);

        $results = [];
        foreach ($products as $product) {
            AttributeValidator::validateAttributesExistence($product, [
                'id_product',
                'visibility',
                'wholesale_price',
                'description',
                'description_short',
                'name',
                'id_manufacturer',
                'reference',
                'stock_quantity',
                'ean13',
                'isbn',
                'upc',
            ]);

            $productId = $product['id_product'];

            $regularPrice = number_format(
                \Product::getPriceStatic($productId, true, null, 6, null, false, false),
                2,
                '.',
                ''
            );
            $finalPrice = number_format(\Product::getPriceStatic($productId, true), 2, '.', '');
            $wholesalePrice = number_format((float) ($product['wholesale_price'] ?? 0), 2, '.', '');

            $formattedProduct = [
                'id' => $productId,
                'visibility' => $product['visibility'],
                'description' => $product['description'],
                'description_short' => $product['description_short'],
                'title' => $product['name'],
                'price' => $regularPrice,
                'final_price' => $finalPrice,
                'wholesale_price' => $wholesalePrice,
                'categories' => $categories[$productId]['names'] ?? [],
                'categories_hierarchy' => $categories[$productId]['hierarchy'] ?? [],
                'categories_last' => $categories[$productId]['last'] ?? [],
                'category_ids' => $categories[$productId]['ids'] ?? [],
                'images' => $images[$productId]['urls'] ?? [],
                'main_image' => $images[$productId]['main'] ?? null,
                'link' => $context->link->getProductLink($productId),
                'reference' => $product['reference'],
                'qty' => $product['stock_quantity'],
                'in_stock' => (int) $product['stock_quantity'] > 0,
                'ean13' => $product['ean13'],
                'isbn' => $product['isbn'],
                'upc' => $product['upc'],
                'tags' => $tags[$productId] ?? [],
            ];

            $discountPercent = $regularPrice > 0 ? round(100 * (($regularPrice - $finalPrice) / $regularPrice), 2) : 0;
            if ($discountPercent > 0) {
                $formattedProduct['discount_percent'] = $discountPercent;
                $formattedProduct['has_discount'] = true;
            }

            $formattedProduct['manufacturer'] = $brands[$product['id_manufacturer']] ?? '';

            foreach ($attributes[$productId] ?? [] as $key => $value) {
                $formattedProduct[$key] = array_values(array_unique($value));
            }

            foreach ($features[$productId] ?? [] as $key => $value) {
                $formattedProduct[$key] = $value;
            }

            if (isset($additionalAttributes[$productId]) && is_array($additionalAttributes[$productId])) {
                $formattedProduct = array_merge($formattedProduct, $additionalAttributes[$productId]);
            }

            $results[] = $formattedProduct;
        }

        return $results;
    }

    public function getProductCategories(array $productIds, int $shopId, int $languageId): array
    {
        $categories = Db::getInstance()->executeS(
            $this->queryProvider->getProductCategoriesQuery($productIds, $shopId, $languageId)
        );

        if (empty($categories)) {
            return [];
        }

        $categoryMap = $this->categoryDataProvider->getCategoryHierarchyMap($shopId, $languageId);

        $productCategories = [];
        foreach ($categories as $category) {
            AttributeValidator::validateAttributesExistence($category, ['id_product', 'id_category']);

            $productId = (int) $category['id_product'];
            $categoryId = (int) $category['id_category'];

            if (!isset($productCategories[$productId])) {
                $productCategories[$productId] = [
                    'ids' => [],
                    'names' => [],
                    'last' => [],
                    'hierarchy' => [],
                ];
            }

            if (!isset($categoryMap[$categoryId])) {
                continue;
            }

            $categoryPath = $this->categoryDataProvider->buildCategoryPath($categoryId, $categoryMap);
            if (empty($categoryPath)) {
                continue;
            }

            $productCategories[$productId]['ids'][] = $categoryId;
            $productCategories[$productId]['hierarchy'][] = $categoryPath;
        }

        foreach ($productCategories as $productId => &$categoryData) {
            $categoryData['names'] = $this->categoryDataProvider->extractFlatNames($categoryData['hierarchy']);
            $categoryData['last'] = $this->categoryDataProvider->extractLastNames($categoryData['hierarchy']);
        }

        return $productCategories;
    }

    public function getProductImages(array $productIds, int $shopId, int $languageId): array
    {
        $images = Db::getInstance()->executeS(
            $this->queryProvider->getProductImagesQuery($productIds, $shopId, $languageId)
        );

        $productImages = [];
        foreach ($images as $image) {
            AttributeValidator::validateAttributesExistence($image, ['id_product', 'link_rewrite', 'id_image']);

            $productId = (int) $image['id_product'];

            $imageUrl = \Context::getContext()->link->getImageLink(
                $image['link_rewrite'],
                $image['id_image'],
                \ImageType::getFormattedName('medium')
            );

            if (!isset($productImages[$productId])) {
                $productImages[$productId] = [
                    'urls' => [],
                    'main' => null,
                ];
            }

            $productImages[$productId]['urls'][] = $imageUrl;

            if (empty($productImages[$productId]['main'])) {
                $productImages[$productId]['main'] = $imageUrl;
            }
        }

        return $productImages;
    }

    public function getProductFeatures(array $productIds, int $shopId, int $languageId): array
    {
        $features = Db::getInstance()->executeS(
            $this->queryProvider->getProductFeaturesQuery($productIds, $shopId, $languageId)
        );

        $productFeatures = [];
        foreach ($features as $feature) {
            AttributeValidator::validateAttributesExistence($feature, ['id_product', 'id_feature', 'feature_value']);

            $productId = (int) $feature['id_product'];

            if (!isset($productFeatures[$productId])) {
                $productFeatures[$productId] = [];
            }

            $productFeatures[$productId]['feature_' . $feature['id_feature']] = $feature['feature_value'];
        }

        return $productFeatures;
    }

    public function getProductManufacturers(array $products, int $shopId): array
    {
        $manufacturerIds = array_column($products, 'id_manufacturer');
        if (empty($manufacturerIds)) {
            return [];
        }

        $manufacturers = Db::getInstance()->executeS(
            $this->queryProvider->getManufacturersQuery($manufacturerIds, $shopId)
        );

        $result = [];
        foreach ($manufacturers as $manufacturer) {
            AttributeValidator::validateAttributesExistence($manufacturer, ['id_manufacturer', 'name']);

            $result[(int) $manufacturer['id_manufacturer']] = $manufacturer['name'];
        }

        return $result;
    }

    public function getProductTags(array $productIds, int $languageId): array
    {
        $tags = Db::getInstance()->executeS(
            $this->queryProvider->getProductTagsQuery($productIds, $languageId)
        );

        $productTags = [];
        foreach ($tags as $tag) {
            AttributeValidator::validateAttributesExistence($tag, ['id_product', 'name']);

            $productId = (int) $tag['id_product'];
            if (!isset($productTags[$productId])) {
                $productTags[$productId] = [];
            }

            $productTags[$productId][] = $tag['name'];
        }

        return $productTags;
    }

    private function getProductAttributes(array $productIds, int $shopId, int $languageId): array
    {
        $attributes = Db::getInstance()->executeS(
            $this->queryProvider->getProductAttributesQuery($productIds, $shopId, $languageId)
        );

        $productAttributes = [];
        foreach ($attributes as $attribute) {
            AttributeValidator::validateAttributesExistence($attribute, [
                'id_product',
                'id_attribute_group',
                'attribute_name',
            ]);

            $productId = (int) $attribute['id_product'];

            if (!isset($productAttributes[$productId])) {
                $productAttributes[$productId] = [];
            }

            $groupKey = 'attribute_group_' . $attribute['id_attribute_group'];
            if (!isset($productAttributes[$productId][$groupKey])) {
                $productAttributes[$productId][$groupKey] = [];
            }

            $productAttributes[$productId][$groupKey][] = $attribute['attribute_name'];
        }

        return $productAttributes;
    }

    protected function getAdditionalProductAttributes(array $productIds, int $shopId, int $languageId): array
    {
        $hookResults = \Hook::exec(
            'actionLupaSearchAddProductAttributes',
            [
                'product_ids' => $productIds,
                'shop_id' => $shopId,
                'language_id' => $languageId,
            ],
            null,
            true
        );

        $attributes = [];

        if (!is_array($hookResults)) {
            return $attributes;
        }

        $validIds = array_flip(array_map('strval', $productIds));

        foreach ($hookResults as $result) {
            if (!is_array($result)) {
                continue;
            }

            foreach ($result as $id => $values) {
                if (!isset($validIds[(string) $id]) || !is_array($values)) {
                    continue;
                }

                $attributes[$id] = array_merge($attributes[$id] ?? [], $values);
            }
        }

        return $attributes;
    }
}
