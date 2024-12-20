<?php
/**
 * @author LupaSearch
 * @copyright LupaSearch
 * @license MIT
 */

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Entity\Db;

class ProductDataProvider
{
    private $queryProvider;

    public function __construct(?QueryProvider $queryProvider = null)
    {
        $this->queryProvider = $queryProvider ?? new QueryProvider();
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

        $results = [];
        foreach ($products as $product) {
            $this->validateAttributesExistence($product, [
                'id_product',
                'price',
                'visibility',
                'description',
                'description_short',
                'name',
                'id_manufacturer',
            ]);

            $productId = $product['id_product'];
            $price = number_format((float) $product['price'], 2, '.', '');
            $finalPrice = number_format(\Product::getPriceStatic($productId, true), 2, '.', '');

            $formattedProduct = [
                'id' => $productId,
                'visibility' => $product['visibility'],
                'description' => $product['description'],
                'description_short' => $product['description_short'],
                'title' => $product['name'],
                'price' => $price,
                'final_price' => $finalPrice,
                'categories' => $categories[$productId]['names'] ?? [],
                'category_ids' => $categories[$productId]['ids'] ?? [],
                'images' => $images[$productId]['urls'] ?? [],
                'main_image' => $images[$productId]['main'] ?? '',
                'link' => $context->link->getProductLink($productId),
            ];

            $discountPercent = $price > 0 ? round(100 * (($price - $finalPrice) / $price), 2) : 0;
            if ($discountPercent > 0) {
                $formattedProduct['discount_percent'] = $discountPercent;
            }

            $formattedProduct['manufacturer'] = $brands[$product['id_manufacturer']] ?? '';

            foreach ($attributes[$productId] ?? [] as $key => $value) {
                $formattedProduct[$key] = array_values(array_unique($value));
            }

            foreach ($features[$productId] ?? [] as $key => $value) {
                $formattedProduct[$key] = $value;
            }

            $results[] = $formattedProduct;
        }

        return $results;
    }

    private function validateAttributesExistence(array $target, array $attributeKeys): void
    {
        foreach ($attributeKeys as $key) {
            if (!array_key_exists($key, $target)) {
                throw new \Exception("Attribute key '$key' is not found within the given target structure: " . \json_encode($target));
            }
        }
    }

    private function getProductCategories(array $productIds, int $shopId, int $languageId): array
    {
        $categories = Db::getInstance()->executeS(
            $this->queryProvider->getProductCategoriesQuery($productIds, $shopId, $languageId)
        );

        $productCategories = [];
        foreach ($categories as $category) {
            $this->validateAttributesExistence($category, ['id_product', 'id_category', 'name']);

            $productId = (int) $category['id_product'];
            $categoryId = (int) $category['id_category'];
            $categoryName = $category['name'];

            if (!isset($productCategories[$productId])) {
                $productCategories[$productId] = [
                    'ids' => [],
                    'names' => [],
                ];
            }

            $productCategories[$productId]['ids'][] = $categoryId;
            $productCategories[$productId]['names'][] = $categoryName;
        }

        return $productCategories;
    }

    private function getProductImages(array $productIds, int $shopId, int $languageId): array
    {
        $images = Db::getInstance()->executeS(
            $this->queryProvider->getProductImagesQuery($productIds, $shopId, $languageId)
        );

        $productImages = [];
        foreach ($images as $image) {
            $this->validateAttributesExistence($image, ['id_product', 'link_rewrite', 'id_image']);

            $productId = (int) $image['id_product'];

            $imageUrl = \Context::getContext()->link->getImageLink(
                $image['link_rewrite'],
                $image['id_image'],
                \ImageType::getFormattedName('medium')
            );

            if (!isset($productImages[$productId])) {
                $productImages[$productId] = [
                    'urls' => [],
                    'main' => '',
                ];
            }

            $productImages[$productId]['urls'][] = $imageUrl;

            if (empty($productImages[$productId]['main'])) {
                $productImages[$productId]['main'] = $imageUrl;
            }
        }

        return $productImages;
    }

    private function getProductAttributes(array $productIds, int $shopId, int $languageId): array
    {
        $attributes = Db::getInstance()->executeS(
            $this->queryProvider->getProductAttributesQuery($productIds, $shopId, $languageId)
        );

        $productAttributes = [];
        foreach ($attributes as $attribute) {
            $this->validateAttributesExistence($attribute, ['id_product', 'id_attribute_group', 'attribute_name']);

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

    private function getProductFeatures(array $productIds, int $shopId, int $languageId): array
    {
        $features = Db::getInstance()->executeS(
            $this->queryProvider->getProductFeaturesQuery($productIds, $shopId, $languageId)
        );

        $productFeatures = [];
        foreach ($features as $feature) {
            $this->validateAttributesExistence($feature, ['id_product', 'id_feature', 'feature_value']);

            $productId = (int) $feature['id_product'];

            if (!isset($productFeatures[$productId])) {
                $productFeatures[$productId] = [];
            }

            $productFeatures[$productId]['feature_' . $feature['id_feature']] = $feature['feature_value'];
        }

        return $productFeatures;
    }

    private function getProductManufacturers(array $products, int $shopId): array
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
            $this->validateAttributesExistence($manufacturer, ['id_manufacturer', 'name']);

            $result[(int) $manufacturer['id_manufacturer']] = $manufacturer['name'];
        }

        return $result;
    }
}
