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
use PrestaShop\PrestaShop\Adapter\Entity\Db;

class CategoryDataProvider
{
    private $queryProvider;

    public function __construct(QueryProvider $queryProvider = null)
    {
        $this->queryProvider = $queryProvider ?? new QueryProvider();
    }

    public function getCategoryHierarchyMap(int $shopId, int $languageId): array
    {
        $categoryHierarchy = Db::getInstance()->executeS(
            $this->queryProvider->getCategoryHierarchyQuery($shopId, $languageId)
        );

        return $this->parseCategoryHierarchy($categoryHierarchy);
    }

    protected function parseCategoryHierarchy(array $categoryHierarchy): array
    {
        $categoryMap = [];
        foreach ($categoryHierarchy as $category) {
            $categoryMap[(int) $category['id_category']] = [
                'name' => $category['name'],
                'id_parent' => (int) $category['id_parent'],
            ];
        }

        return $categoryMap;
    }

    public function buildCategoryPath(int $categoryId, array $categoryMap): string
    {
        $path = [];
        while (isset($categoryMap[$categoryId])) {
            $path[] = $categoryMap[$categoryId]['name'];
            $categoryId = $categoryMap[$categoryId]['id_parent'];
        }

        return implode(' > ', array_reverse($path));
    }

    public function extractFlatNames(array $hierarchies): array
    {
        $flatNames = [];
        foreach ($hierarchies as $hierarchy) {
            $parts = explode(' > ', $hierarchy);
            $flatNames = array_merge($flatNames, $parts);
        }

        return array_values(array_unique($flatNames));
    }

    public function extractLastNames(array $hierarchies): array
    {
        $lastNames = [];
        foreach ($hierarchies as $hierarchy) {
            $parts = explode(' > ', $hierarchy);
            $lastNames[] = end($parts);
        }

        return array_values(array_unique($lastNames));
    }
}
