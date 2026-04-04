<?php

namespace local_ab_testing\stripe;

use auth_stripe\core;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\util\price_display_util;
use auth_stripe\util\util;
use local_ab_testing\test\premium_description_test;

class premium_description_test_price_card extends \auth_stripe\price_card {

    protected $_default_ab_info;

    public function __construct(product $product, price $price){
        parent::__construct($product, $price);
        $this->_default_ab_info = premium_description_test::get_default_campaign();
    }

    public function get_payment_data(){
        $this->price = premium_description_test::preprocess_price($this->price);
        return [
            'plan_name'  => $this->price->plan_name,
            'tier'       => $this->product->tier,
            'price_info' => $this->displayed_price,
            'priceid'    => $this->price->id,
            'period'     => $this->price->period,
            'plan_info'  => $this->get_plan_info(),
            'price_sum'  => price_display_util::format_price_sum($this->price, $this->second_price),
        ];
    }

    public function get_plan_info(): string{
        if ($this->product->sql_page != core::MAIN_PAGE){
            return parent::get_plan_info();
        }

        $current_campaign = premium_description_test::get_user_campaign();
        if (empty($current_campaign) || $current_campaign == $this->_default_ab_info){
            return parent::get_plan_info();
        }

        $template = 'premium_description';
        if ($this->price->period == core::PERIOD_YEAR){
            $template .= '_annual';
        }

        return static::_get_card($template, ['period' => util::format_premium_period($this->price)]);
    }

    protected static function _get_card($template, $context = []){
        global $OUTPUT;
        return $OUTPUT->render_from_template('local_ab_testing/cards/'.$template, $context);
    }
}