<?php

namespace local_ab_testing\stripe\pages;

use auth_plugin_stripe;
use local_ab_testing\stripe\premium_description_test_product;
use local_ab_testing\stripe\price;

class premium_description {

    /**
     * @param auth_plugin_stripe $authplugin
     * @param string $page_type
     */
    public static function payment_page($authplugin, $page_type, $token = '', $coupon = null){
        global $PAGE;
        $output = $PAGE->get_renderer('auth_'.$authplugin->authtype);

        stripe_body_add_payment_class();
        \auth_stripe\stripe::set_page($page_type);

        if (!empty($token)){
            $product = premium_description_test_product::get_by_page($page_type);
            $prices = price::get_all_by_token($token);
            $product_cards = premium_description_test_product::render_prices($product, $prices);
        } else {
            $products = premium_description_test_product::get_all_by_page($page_type);
            $product_cards = premium_description_test_product::render_product_cards($products);
        }

        $coupon_allowed = \auth_stripe\core::is_coupon_allowed($page_type);
        if ($coupon_allowed){
            foreach ($product_cards as $product_card){
                $coupon_allowed = $coupon_allowed && \auth_stripe\core::is_period_price($product_card['period']);
            }
        }

        $form = $authplugin->render_payment_stripe_form($coupon_allowed, $coupon);
        $template_data = stripe_get_layout_context($form, $product_cards);
        if (\auth_stripe\core::COACHING_PAGE == $page_type){
            $template_data['additional_product_info'] = $output->render_from_template('auth_stripe/cards/coaching_additional', []);
        }

        echo $output->header();
        echo $output->render_from_template('auth_stripe/payment_layout', $template_data);
        echo $output->footer();
        die;
    }
}