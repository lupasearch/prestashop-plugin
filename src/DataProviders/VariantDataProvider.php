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

class VariantDataProvider
{
    private $queryProvider;
    private $categoryDataProvider;
    private $productDataProvider;

    public function __construct(
        QueryProvider $queryProvider = null,
        CategoryDataProvider $categoryDataProvider = null,
        ProductDataProvider $productDataProvider = null
    ) {
        $this->queryProvider = $queryProvider ?? new QueryProvider();
        $this->categoryDataProvider = $categoryDataProvider ?? new CategoryDataProvider();
        $this->productDataProvider = $productDataProvider ?? new ProductDataProvider();
    }

    public function getFormattedVariants(int $page = 1, int $limit = 20): array
    {
        $context = \Context::getContext();
        $languageId = $context->language->id;
        $shopId = $context->shop->id;

        $offset = ($page - 1) * $limit;

        $variants = Db::getInstance()->executeS(
            $this->queryProvider->getPaginatedVariantsQuery($shopId, $languageId, $offset, $limit)
        );

        if (empty($variants)) {
            return [];
        }

        $productIds = array_column($variants, 'id_product');
        $combinationIds = array_filter(array_column($variants, 'combination_id'));

        $categories = $this->productDataProvider->getProductCategories($productIds, $shopId, $languageId);
        $productImages = $this->productDataProvider->getProductImages($productIds, $shopId, $languageId);
        $combinationImages = $this->getCombinationImages($combinationIds, $shopId, $languageId);
        $brands = $this->productDataProvider->getProductManufacturers($variants, $shopId);
        $attributes = $this->getCombinationAttributes($combinationIds, $shopId, $languageId);
        $features = $this->productDataProvider->getProductFeatures($productIds, $shopId, $languageId);

        $results = [];
        foreach ($variants as $variant) {
            AttributeValidator::validateAttributesExistence($variant, [
                'id_product',
                'combination_id',
                'variant_wholesale_price',
                'visibility',
                'description',
                'name',
                'id_manufacturer',
                'stock_quantity',
            ]);

            $productId = $variant['id_product'];
            $combinationId = $variant['combination_id'];
            $variantType = $variant['variant_type'];

            $regularPrice = number_format(
                \Product::getPriceStatic($productId, true, $combinationId, 6, null, false, false) ?? 0,
                2,
                '.',
                ''
            );
            $finalPrice = number_format(\Product::getPriceStatic($productId, true, $combinationId) ?? 0, 2, '.', '');
            $wholesalePrice = number_format((float) ($variant['variant_wholesale_price'] ?? 0), 2, '.', '');

            $formattedVariant = [
                'id' => $combinationId ?? $productId,
                'product_id' => $productId,
                'type' => $variantType,
                'visibility' => $variant['visibility'],
                'description' => $variant['description'],
                'description_short' => $variant['description_short'] ?? '',
                'title' => $variant['name'],
                'price' => $regularPrice,
                'final_price' => $finalPrice,
                'wholesale_price' => $wholesalePrice,
                'categories' => $categories[$productId]['names'] ?? [],
                'categories_hierarchy' => $categories[$productId]['hierarchy'] ?? [],
                'categories_last' => $categories[$productId]['last'] ?? [],
                'category_ids' => $categories[$productId]['ids'] ?? [],
                'images' => $variantType === 'combination'
                        ? $combinationImages[$combinationId]['urls'] ?? []
                        : $productImages[$productId]['urls'] ?? [],
                'main_image' => $combinationImages[$combinationId]['main'] ?? ($productImages[$productId]['main'] ?? null),
                'link' => $context->link->getProductLink(
                    $productId,
                    null,
                    null,
                    null,
                    $languageId,
                    null,
                    $combinationId
                ),
                'reference' => $variant['reference'],
                'manufacturer' => $brands[$variant['id_manufacturer']] ?? '',
                'qty' => $variant['stock_quantity'],
                'in_stock' => (int) $variant['stock_quantity'] > 0,
            ];

            $discountPercent = $regularPrice > 0 ? round(100 * (($regularPrice - $finalPrice) / $regularPrice), 2) : 0;
            if ($discountPercent > 0) {
                $formattedVariant['discount_percent'] = $discountPercent;
                $formattedVariant['has_discount'] = true;
            }

            foreach ($attributes[$combinationId] ?? [] as $key => $value) {
                $formattedVariant[$key] = array_values(array_unique($value));
            }

            foreach ($features[$productId] ?? [] as $key => $value) {
                $formattedVariant[$key] = $value;
            }

            $results[] = $formattedVariant;
        }

        return $results;
    }

    private function getCombinationImages(array $combinationIds, int $shopId, int $languageId): array
    {
        $images = Db::getInstance()->executeS(
            $this->queryProvider->getCombinationImagesQuery($combinationIds, $shopId, $languageId)
        );

        $combinationImages = [];
        foreach ($images as $image) {
            AttributeValidator::validateAttributesExistence($image, [
                'id_product_attribute',
                'link_rewrite',
                'id_image',
            ]);

            $combinationId = (int) $image['id_product_attribute'];

            $imageUrl = \Context::getContext()->link->getImageLink(
                $image['link_rewrite'],
                $image['id_image'],
                \ImageType::getFormattedName('medium')
            );

            if (!isset($combinationImages[$combinationId])) {
                $combinationImages[$combinationId] = [
                    'urls' => [],
                    'main' => null,
                ];
            }

            $combinationImages[$combinationId]['urls'][] = $imageUrl;

            if (empty($combinationImages[$combinationId]['main'])) {
                $combinationImages[$combinationId]['main'] = $imageUrl;
            }
        }

        return $combinationImages;
    }

    private function getCombinationAttributes(array $combinationIds, int $shopId, int $languageId): array
    {
        if (empty($combinationIds)) {
            return [];
        }

        $attributes = Db::getInstance()->executeS(
            $this->queryProvider->getCombinationAttributesQuery($combinationIds, $shopId, $languageId)
        );

        $combinationAttributes = [];
        foreach ($attributes as $attribute) {
            AttributeValidator::validateAttributesExistence($attribute, [
                'id_product_attribute',
                'id_attribute_group',
                'attribute_name',
            ]);

            $combinationId = (int) $attribute['id_product_attribute'];

            if (!isset($combinationAttributes[$combinationId])) {
                $combinationAttributes[$combinationId] = [];
            }

            $groupKey = 'attribute_group_' . $attribute['id_attribute_group'];
            if (!isset($combinationAttributes[$combinationId][$groupKey])) {
                $combinationAttributes[$combinationId][$groupKey] = [];
            }

            $combinationAttributes[$combinationId][$groupKey][] = $attribute['attribute_name'];
        }

        return $combinationAttributes;
    }
}
