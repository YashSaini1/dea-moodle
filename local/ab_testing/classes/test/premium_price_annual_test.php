<?php

namespace local_ab_testing\test;

use auth_stripe\model\price;
use local_ab_testing\test\base\base_test;

class premium_price_annual_test extends base_test {

    const TEST_NAME = 'premium_price_annual';

    public static function hook_page_open(){
        global $PAGE;
        if (!static::validate_redirect()){
            redirect($PAGE->url);
        }
    }

    public static function preprocess_price(price $price){
        return $price;
    }
}

premium_price_annual_test::init();