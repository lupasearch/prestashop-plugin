<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin;

use PrestaShop\PrestaShop\Adapter\Entity\DbQuery;

class QueryProvider
{
    public function getProductsCount(): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*) as total');
        $sql->from('product', 'p');
        $sql->where('p.active = 1');

        return $sql;
    }

    public function getPaginatedProductsQuery(int $languageId, int $offset, int $limit): DbQuery
    {
        $sql = new DbQuery();
        $sql->select(
            'p.id_product, p.price, p.reference, p.id_manufacturer, p.visibility, p.product_type, pl.name, pl.description, pl.description_short'
        );
        $sql->from('product', 'p');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $languageId);
        $sql->where('p.active = 1');
        $sql->orderBy('p.id_product ASC');
        $sql->limit($limit, $offset);

        return $sql;
    }

    public function getProductCategoriesQuery(array $productIds, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('pc.id_product, c.id_category, cl.name');
        $sql->from('category_product', 'pc');
        $sql->leftJoin('category_lang', 'cl', 'pc.id_category = cl.id_category AND cl.id_lang = ' . $languageId);
        $sql->leftJoin('category', 'c', 'c.id_category = pc.id_category');
        $sql->where('pc.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');
        $sql->orderBy('c.level_depth ASC');

        return $sql;
    }

    public function getProductImagesQuery(array $productIds, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('i.id_product, i.id_image, pl.link_rewrite');
        $sql->from('image', 'i');
        $sql->leftJoin('product_lang', 'pl', 'i.id_product = pl.id_product AND pl.id_lang = ' . $languageId);
        $sql->where('i.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');

        return $sql;
    }

    public function getProductAttributesQuery(array $productIds, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('pa.id_product, a.id_attribute_group, al.name as attribute_name');
        $sql->from('product_attribute_combination', 'pac');
        $sql->leftJoin('attribute', 'a', 'pac.id_attribute = a.id_attribute');
        $sql->leftJoin('attribute_lang', 'al', 'a.id_attribute = al.id_attribute AND al.id_lang = ' . $languageId);
        $sql->leftJoin('product_attribute', 'pa', 'pac.id_product_attribute = pa.id_product_attribute');
        $sql->where('pa.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');

        return $sql;
    }

    public function getProductFeaturesQuery(array $productIds, int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('pf.id_feature, pf.id_product, fvl.value as feature_value');
        $sql->from('feature_product', 'pf');
        $sql->leftJoin(
            'feature_value_lang',
            'fvl',
            'pf.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . $languageId
        );
        $sql->where('pf.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')');

        return $sql;
    }

    public function getManufacturersQuery(array $manufacturerIds): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('m.id_manufacturer, m.name');
        $sql->from('manufacturer', 'm');
        $sql->where('m.id_manufacturer IN (' . implode(',', array_map('intval', $manufacturerIds)) . ')');

        return $sql;
    }

    public function getAttributeGroupsQuery(int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('agl.id_attribute_group, agl.name');
        $sql->from('attribute_group_lang', 'agl');
        $sql->where('agl.id_lang = ' . $languageId);

        return $sql;
    }

    public function getFeaturesQuery(int $languageId): DbQuery
    {
        $sql = new DbQuery();
        $sql->select('fl.id_feature, fl.name');
        $sql->from('feature_lang', 'fl');
        $sql->where('fl.id_lang = ' . $languageId);

        return $sql;
    }
}
