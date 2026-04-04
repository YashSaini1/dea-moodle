<?php

namespace local_ab_testing\stripe;

use local_ab_testing\test\premium_price_test;
use local_ab_testing\test\premium_price_annual_test;

class premium_price_test_product extends \auth_stripe\model\product {

    public static function render_product_cards($products){
        $user_campaign = premium_price_annual_test::get_user_campaign();
        if (empty($user_campaign)){
            $user_campaign = premium_price_annual_test::get_default_campaign();
        }

        $template_data = [];
        foreach ($products as $product){
            $prices = \auth_stripe\model\price::get_product_prices($product->id);
            $result_prices = [];
            foreach ($prices as $price){
                if (!empty($price->ab_info) && $price->ab_info != $user_campaign){
                    continue;
                }
                $result_prices[] = $price;
            }

            $template_data = static::render_prices($product, $result_prices, $template_data);
        }

        return $template_data;
    }

    public static function render_prices($product, $prices, $template_data = []){
        foreach ($prices as $price){
            if (!$price->enabled){
                continue;
            }

            if (empty($price->dependency)){
                $price_card = new premium_price_test_price_card($product, $price);
                $template_data[] = $price_card->get_payment_data();
            }
        }
        return $template_data;
    }
}