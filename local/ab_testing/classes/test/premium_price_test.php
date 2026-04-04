<?php

namespace local_ab_testing\test;

use auth_stripe\model\price;
use local_ab_testing\test\base\base_test;

class premium_price_test extends base_test {

    const TEST_NAME = 'premium_price';

    public static function hook_page_open(){
        global $PAGE;
        if (!static::validate_redirect()){
            redirect($PAGE->url);
        }
    }

    public static function preprocess_price(price $price){
        return $price;
        // Now monthly and annually prices are different, so we don't need to change anything
        if (empty(static::get_user_campaign()) || static::get_user_campaign() == static::get_default_campaign() ||
            $price->period != \auth_stripe\core::PERIOD_YEAR){
            return $price;
        }

        // hard code change user annual save
        $price->plan_name = str_replace('53%', '30%', $price->plan_name);
        $price->ab_info = static::get_user_campaign();
        return $price;
    }
}

premium_price_test::init();