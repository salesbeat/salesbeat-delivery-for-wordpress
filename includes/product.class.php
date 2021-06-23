<?php

namespace Salesbeat\Inc;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\Product', false))
    return new Product();

class Product
{
    private $fromWeightUnit = '';
    private $fromDimensionUnit = '';

    private function __constructor()
    {
        $this->fromWeightUnit = get_option('woocommerce_weight_unit');
        $this->fromDimensionUnit = get_option('woocommerce_dimension_unit');
    }

    public function getProductList($productList, $isAdmin = false)
    {
        if (empty($productList)) return [];

        $products = [];
        foreach ($productList as $product)
            $products[] = $this->getProduct($product, $isAdmin);

        return $products;
    }

    public function getProduct($product, $isAdmin = false)
    {
        $Product = !empty($product['variation_id']) ?
            new \WC_Product_Variation($product['variation_id']) :
            new \WC_Product_Simple($product['product_id']);

        return [
            'id' => $Product->get_id(),
            'name' => $Product->get_name(),
            'price_to_pay' => ceil($Product->get_price()),
            'price_insurance' => ceil($Product->get_price()),
            'weight' => ceil(wc_get_weight($Product->get_weight(), 'g', $this->fromWeightUnit)),
            'x' => ceil(wc_get_dimension($Product->get_length(), 'sm', $this->fromDimensionUnit)),
            'y' => ceil(wc_get_dimension($Product->get_width(), 'sm', $this->fromDimensionUnit)),
            'z' => ceil(wc_get_dimension($Product->get_height(), 'sm', $this->fromDimensionUnit)),
            'quantity' => ceil($product['quantity']),
        ];
    }
}

return new Product();