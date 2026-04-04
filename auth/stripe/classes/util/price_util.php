<?php

namespace auth_stripe\util;

use auth_stripe\form\admin_price;

class price_util {

    public static function prepare_price_formdata(array $prices, $data = []){
        if (empty($data)){
            $data = [
                'period'     => [],
                'price'      => [],
                'max_times'  => [],
                'plan_name'  => [],
                'dependency' => [],
            ];
        }

        if (!empty($prices)){
            foreach ($prices as $price){
                $data['period'][] = admin_price::get_period_index($price->period);
                $data['price'][] = $price->price;
                $data['max_times'][] = $price->max_times;
                $data['plan_name'][] = $price->plan_name;
                $data['dependency'][] = (int)($price->dependency != 0);
            }
        }
        return $data;
    }
}