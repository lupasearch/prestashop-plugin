<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin;

use PrestaShop\PrestaShop\Adapter\Entity\Db;
use Context;
use Product;

class ProductDataProvider
{
    private $queryProvider;

    public function __construct(QueryProvider $queryProvider = null)
    {
        $this->queryProvider = $queryProvider ?? new QueryProvider();
    }

    public function getFormattedProducts(int $page = 1, int $limit = 20): array
    {
        $languageId = Context::getContext()->language->id;

        $offset = ($page - 1) * $limit;

        $products = Db::getInstance()->executeS(
            $this->queryProvider->getPaginatedProductsQuery($languageId, $offset, $limit)
        );

        if (empty($products)) {
            return [];
        }

        $productIds = array_column($products, 'id_product');

        $categories = $this->getProductCategories($productIds, $languageId);
        $images = $this->getProductImages($productIds);
        $brands = $this->getProductManufacturers($products);
        $attributes = $this->getProductAttributes($productIds, $languageId);
        $features = $this->getProductFeatures($productIds, $languageId);

        $results = [];
        foreach ($products as $product) {
            $productId = $product['id_product'];
            $price = number_format((float) $product['price'], 2, '.', '');
            $finalPrice = number_format(Product::getPriceStatic($productId, true), 2, '.', '');

            $formattedProduct = [
                'id' => $productId,
                'visibility' => $product['visibility'],
                'product_type' => $product['product_type'],
                'description' => $product['description'],
                'description_short' => $product['description_short'],
                'title' => $product['name'],
                'price' => $price,
                'final_price' => $finalPrice,
                'categories' => $categories[$productId]['names'] ?? [],
                'category_ids' => $categories[$productId]['ids'] ?? [],
                'images' => $images[$productId]['urls'] ?? [],
                'main_image' => $images[$productId]['main'] ?? '',
                'link' => Context::getContext()->link->getProductLink($productId),
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

    private function getProductCategories(array $productIds, int $languageId): array
    {
        $categories = Db::getInstance()->executeS(
            $this->queryProvider->getProductCategoriesQuery($productIds, $languageId)
        );

        $productCategories = [];
        foreach ($categories as $category) {
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

    private function getProductImages(array $productIds): array
    {
        $languageId = Context::getContext()->language->id;
        $images = Db::getInstance()->executeS($this->queryProvider->getProductImagesQuery($productIds, $languageId));

        $productImages = [];
        foreach ($images as $image) {
            $productId = (int) $image['id_product'];

            $imageUrl = Context::getContext()->link->getImageLink(
                $image['link_rewrite'],
                $image['id_image'],
                'medium_default'
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

    private function getProductAttributes(array $productIds, int $languageId): array
    {
        $attributes = Db::getInstance()->executeS(
            $this->queryProvider->getProductAttributesQuery($productIds, $languageId)
        );

        $productAttributes = [];
        foreach ($attributes as $attribute) {
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

    private function getProductFeatures(array $productIds, int $languageId): array
    {
        $features = Db::getInstance()->executeS(
            $this->queryProvider->getProductFeaturesQuery($productIds, $languageId)
        );

        $productFeatures = [];
        foreach ($features as $feature) {
            $productId = (int) $feature['id_product'];

            if (!isset($productFeatures[$productId])) {
                $productFeatures[$productId] = [];
            }

            $productFeatures[$productId]['feature_' . $feature['id_feature']] = $feature['feature_value'];
        }

        return $productFeatures;
    }

    private function getProductManufacturers(array $products): array
    {
        $manufacturerIds = array_column($products, 'id_manufacturer');
        if (empty($manufacturerIds)) {
            return [];
        }

        $manufacturers = Db::getInstance()->executeS($this->queryProvider->getManufacturersQuery($manufacturerIds));

        $result = [];
        foreach ($manufacturers as $manufacturer) {
            $result[(int) $manufacturer['id_manufacturer']] = $manufacturer['name'];
        }

        return $result;
    }
}
