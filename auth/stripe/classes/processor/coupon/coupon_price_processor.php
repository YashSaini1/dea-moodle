<?php

namespace auth_stripe\processor\coupon;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\model\price;
use auth_stripe\util\price_display_util;

class coupon_price_processor {

    protected coupon $_coupon;

    /**
     * @param coupon $_coupon
     */
    public function __construct(coupon $_coupon){
        $this->_coupon = $_coupon;
    }

    public function generate_new_prices_name($price_ids){
        $result = [];

        $prices = price::get_all(['id' => $price_ids]);
        foreach ($prices as $price){
            $new_sum = price_display_util::format_displayed_price($price, false, $this->_coupon);
            $result[] = [
                'price_id'       => $price->id,
                'price_discount' => \html_writer::tag('p', '$'.$new_sum, ['class' => 'discounted-price']),
            ];
        }

        return $result;
    }

    public function generate_coupon_user_message(){
        $make_htm = fn($content) => \html_writer::div($content, 'coupon_user_message');

        $duration = $this->_coupon->duration;
        if ($duration == coupon::DURATION_ONCE){
            return $make_htm(core::str('coupon:duration:once'));
        }
        if ($duration == coupon::DURATION_FOREVER){
            return $make_htm(core::str('coupon:duration:forever'));
        }

        if ($duration == coupon::DURATION_REPEATING){
            return $make_htm(core::str('coupon:duration:repeating', $this->_coupon->duration_in_months));
        }

        return '';
    }

    public function get_coupon_discount(){
        if (!empty($this->_coupon->amount_off)){
            return price_display_util::format_price($this->_coupon->amount_off / 100).' '.strtoupper($this->_coupon->currency);
        }

        if (!empty($this->_coupon->percent_off)){
            return ($this->_coupon->percent_off / 100).'%';
        }

        return '';
    }
}