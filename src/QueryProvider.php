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

use PrestaShop\PrestaShop\Adapter\Entity\DbQuery;

class QueryProvider
{
    public function getProductsCount(int $shopId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*) as total');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . $shopId);
        $sql->where('p.active = 1 AND ps.active = 1');

        return $sql;
    }

    public function getPaginatedProductsQuery(int $shopId, int $languageId, int $offset, int $limit): DbQuery
    {
        $sql = new DbQuery();
        $sql->select(
            'p.id_product, p.reference, p.id_manufacturer, p.visibility, p.wholesale_price, pl.name, pl.description, pl.description_short, sa.quantity AS stock_quantity'
        );
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . $shopId);
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.id_product = pl.id_product AND pl.id_shop = ' . $shopId . ' AND pl.id_lang = ' . $languageId
        );
        $sql->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = ' . (int) $shopId);
        $sql->where('p.active = 1 AND ps.active = 1');
        $sql->orderBy('p.id_product ASC');
        $sql->limit($limit, $offset);

        return $sql;
    }

    public function getProductCategoriesQuery(array $productIds, int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('cp.id_product, c.id_category, cl.name');
        $sql->from('category_product', 'cp');
        $sql->innerJoin('category_shop', 'cs', 'cs.id_category = cp.id_category AND cs.id_shop = ' . $shopId);
        $sql->leftJoin(
            'category_lang',
            'cl',
            'cp.id_category = cl.id_category AND cl.id_shop = ' . $shopId . ' AND cl.id_lang = ' . $languageId
        );
        $sql->leftJoin('category', 'c', 'c.id_category = cp.id_category');
        $sql->where('cp.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');
        $sql->orderBy('c.level_depth ASC');

        return $sql;
    }

    public function getProductImagesQuery(array $productIds, int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('i.id_product, i.id_image, pl.link_rewrite');
        $sql->from('image', 'i');
        $sql->leftJoin(
            'product_lang',
            'pl',
            'i.id_product = pl.id_product AND pl.id_shop = ' . $shopId . ' AND pl.id_lang = ' . $languageId
        );
        $sql->where('i.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');

        return $sql;
    }

    public function getProductAttributesQuery(array $productIds, int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('pa.id_product, a.id_attribute_group, al.name as attribute_name');
        $sql->from('product_attribute_combination', 'pac');
        $sql->leftJoin('attribute', 'a', 'pac.id_attribute = a.id_attribute');
        $sql->leftJoin('attribute_lang', 'al', 'a.id_attribute = al.id_attribute AND al.id_lang = ' . $languageId);
        $sql->innerJoin('attribute_shop', 'as', 'as.id_attribute = a.id_attribute AND as.id_shop = ' . $shopId);
        $sql->leftJoin('product_attribute', 'pa', 'pac.id_product_attribute = pa.id_product_attribute');
        $sql->innerJoin(
            'product_attribute_shop',
            'pas',
            'pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = ' . $shopId
        );
        $sql->where('pa.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');

        return $sql;
    }

    public function getProductFeaturesQuery(array $productIds, int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('fp.id_feature, fp.id_product, fvl.value as feature_value');
        $sql->from('feature_product', 'fp');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = fp.id_product AND ps.id_shop = ' . $shopId);
        $sql->innerJoin('feature_shop', 'fs', 'fs.id_feature = fp.id_feature AND fs.id_shop = ' . $shopId);
        $sql->leftJoin(
            'feature_value_lang',
            'fvl',
            'fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . $languageId
        );
        $sql->where('fp.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');

        return $sql;
    }

    public function getManufacturersQuery(array $manufacturerIds, int $shopId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('m.id_manufacturer, m.name');
        $sql->from('manufacturer', 'm');
        $sql->innerJoin(
            'manufacturer_shop',
            'ms',
            'ms.id_manufacturer = m.id_manufacturer AND ms.id_shop = ' . $shopId
        );
        $sql->where('m.id_manufacturer IN (' . implode(',', array_map('intval', $manufacturerIds)) . ')');

        return $sql;
    }

    public function getAttributeGroupsQuery(int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('agl.id_attribute_group, agl.name');
        $sql->from('attribute_group_lang', 'agl');
        $sql->innerJoin(
            'attribute_group_shop',
            'ags',
            'ags.id_attribute_group = agl.id_attribute_group AND ags.id_shop = ' . $shopId
        );
        $sql->where('agl.id_lang = ' . $languageId);

        return $sql;
    }

    public function getFeaturesQuery(int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('fl.id_feature, fl.name');
        $sql->from('feature_lang', 'fl');
        $sql->innerJoin('feature_shop', 'fs', 'fs.id_feature = fl.id_feature AND fs.id_shop = ' . $shopId);
        $sql->where('fl.id_lang = ' . $languageId);

        return $sql;
    }

    public function getCategoryHierarchyQuery(int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('c.id_category, c.id_parent, cl.name');
        $sql->from('category', 'c');
        $sql->innerJoin('category_shop', 'cs', 'cs.id_category = c.id_category AND cs.id_shop = ' . $shopId);
        $sql->leftJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_lang = ' . $languageId);
        $sql->where('c.active = 1 AND c.id_parent NOT IN (0, 1)');

        return $sql;
    }

    public function getVariantsCount(int $shopId): DbQuery
    {
        $sql = new DbQuery();

        $sql->select('COUNT(*) as total');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . $shopId);
        $sql->leftJoin('product_attribute', 'pa', 'pa.id_product = p.id_product');
        $sql->where('p.active = 1 AND ps.active = 1');

        return $sql;
    }

    public function getPaginatedVariantsQuery(int $shopId, int $languageId, int $offset, int $limit): DbQuery
    {
        $sql = new DbQuery();
        $sql->select(
            'p.id_product, pa.id_product_attribute AS combination_id, COALESCE(NULLIF(COALESCE(pas.wholesale_price, ps.wholesale_price), 0), ps.wholesale_price) AS variant_wholesale_price, p.reference, p.id_manufacturer, p.visibility, pl.name, pl.description, pl.description_short, ps.price AS base_price, IF(pa.id_product_attribute IS NULL, "simple", "combination") AS variant_type, sa.quantity AS stock_quantity'
        );
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . $shopId);
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.id_product = pl.id_product AND pl.id_shop = ' . $shopId . ' AND pl.id_lang = ' . $languageId
        );
        $sql->leftJoin('product_attribute', 'pa', 'pa.id_product = p.id_product');
        $sql->leftJoin(
            'product_attribute_shop',
            'pas',
            'pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = ' . $shopId
        );
        $sql->leftJoin(
            'stock_available',
            'sa',
            'sa.id_product = p.id_product AND sa.id_product_attribute = IFNULL(pa.id_product_attribute, 0) AND sa.id_shop = ' .
                (int) $shopId
        );
        $sql->where('p.active = 1 AND ps.active = 1');
        $sql->orderBy('p.id_product ASC, pa.id_product_attribute ASC');
        $sql->limit($limit, $offset);

        return $sql;
    }

    public function getCombinationImagesQuery(array $combinationIds, int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('pai.id_product_attribute, i.id_image, pl.link_rewrite');
        $sql->from('product_attribute_image', 'pai');
        $sql->innerJoin('image', 'i', 'pai.id_image = i.id_image');
        $sql->innerJoin(
            'product_lang',
            'pl',
            'i.id_product = pl.id_product AND pl.id_shop = ' . $shopId . ' AND pl.id_lang = ' . $languageId
        );
        $sql->where('pai.id_product_attribute IN (' . implode(',', array_map('intval', $combinationIds)) . ')');

        return $sql;
    }

    public function getCombinationAttributesQuery(array $combinationIds, int $shopId, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('pac.id_product_attribute, a.id_attribute_group, al.name as attribute_name');
        $sql->from('product_attribute_combination', 'pac');
        $sql->leftJoin('attribute', 'a', 'pac.id_attribute = a.id_attribute');
        $sql->leftJoin('attribute_lang', 'al', 'a.id_attribute = al.id_attribute AND al.id_lang = ' . $languageId);
        $sql->innerJoin('attribute_shop', 'as', 'as.id_attribute = a.id_attribute AND as.id_shop = ' . $shopId);
        $sql->where('pac.id_product_attribute IN (' . implode(',', array_map('intval', $combinationIds)) . ')');

        return $sql;
    }
}
