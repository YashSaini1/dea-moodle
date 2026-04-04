<?php

namespace local_ab_testing\stripe;

use auth_stripe\model\product;

class premium_description_test_product extends product {

    public static function render_product_cards($products){
        $template_data = [];
        foreach ($products as $product){
            $prices = price::get_product_prices($product->id);
            $template_data = static::render_prices($product, $prices, $template_data);
        }

        return $template_data;
    }

    public static function render_prices($product, $prices, $template_data = []){
        foreach ($prices as $price){
            if (!$price->enabled){
                continue;
            }

            if (empty($price->dependency)){
                $price_card = new premium_description_test_price_card($product, $price);
                $template_data[] = $price_card->get_payment_data();
            }
        }
        return $template_data;
    }
}