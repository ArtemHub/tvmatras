<?php

class TvMatrasExporter
{

    public static $products;

    private static $query = 'SELECT
               p.id_product,
               p.id_manufacturer,
               p.reference AS sku,
               p.price AS price,
               pl.link_rewrite AS link_rewrite,
               pl.description_short AS description_short,
               pl.name AS name,
               m.name AS manufacturer,

               pa.id_product_attribute AS p_attr_id,
               pa.price AS at_price,
               pa.reference AS pa_sku,
               pal.id_attribute AS attr_id,
               pal.name AS attr_name,
               
               sp.reduction as reduction,
               sp.reduction_type as reduction_type,

               cl.link_rewrite AS cat_link_rewrite,
               cl.name AS category,
               cl.id_category AS id_category

            FROM ps_product p

            LEFT OUTER JOIN ps_product_attribute pa ON (p.id_product = pa.id_product)
            LEFT OUTER JOIN ps_manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
            LEFT JOIN ps_product_lang pl ON (p.id_product = pl.id_product)
            LEFT OUTER JOIN ps_product_attribute_combination pac ON (pa.id_product_attribute = pac.id_product_attribute)
            LEFT OUTER JOIN ps_attribute_lang pal ON (pac.id_attribute = pal.id_attribute)
            LEFT JOIN ps_category_product cp ON (p.id_product = cp.id_product)
            LEFT JOIN ps_category_lang cl ON (cp.id_category = cl.id_category)
            LEFT JOIN ps_category c ON (cp.id_category = c.id_category)
            
            LEFT OUTER JOIN ps_specific_price sp ON (sp.id_product = p.id_product)

            WHERE pl.id_lang = 1
                  AND (pal.id_lang = 1 OR pal.id_lang IS NULL)
                  AND p.active = 1
                  AND cp.id_category = p.id_category_default

            GROUP BY p_attr_id, attr_id, case when attr_id is Null then p.id_product else NULL end
            ORDER BY p.id_product, pac.id_product_attribute, attr_id ASC';

    public static function Export()
    {
        self::getProducts();
    }

    private static function getProducts()
    {
        self::$products = array();

        if ($records = Db::getInstance()->ExecuteS(self::$query)) {
            foreach ($records as $record) {
                $index = (!isset($record['p_attr_id']) ? $record['id_product'] : $record['p_attr_id']);

                if (!isset(self::$products[$index])) {

                    $product = $record;

                    // TODO: do without Link Object
                    $link = new Link();
                    $product['link'] = $link->getProductLink($record['id_product'], $record['link_rewrite'], $record['cat_link_rewrite'], null, 1, 1, (($record['p_attr_id']) ? $record['p_attr_id'] : null), false, false, true);

                    // TODO: create function for price calculation
                    if (floatval($product['at_price']) != 0) {
                        $art_price = floatval($product['at_price']) * 1000000;
                        $price = floatval($product['price']) * 1000000;
                        $price = ($art_price + $price);
                        $product['price'] = floor($price / 1000000);
                    }

                    $product['sku'] = ((!$product['pa_sku']) ? $product['sku'] : $product['pa_sku']);

                    $product['size'] = $record['attr_name'];
                    $product['description'] = strip_tags($record['description_short']);

                    self::$products[$index] = $product;
                } else {
                    self::$products[$index]['attr_name'] .= ' ' . $record['attr_name'];
                }
            }
        }
    }

    protected static function Escape($string)
    {
        return str_replace(
            array('<','>','&','\'','"'),
            array('&lt;','&gt;','&amp;','&apos;','&quot;'),
            $string
        );
    }
}