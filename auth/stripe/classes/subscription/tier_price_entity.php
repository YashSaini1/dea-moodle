<?php

namespace auth_stripe\subscription;

use auth_stripe\model\coupon;
use auth_stripe\model\price;
use auth_stripe\model\user_tier;

class tier_price_entity {

    public ?user_tier $tier;
    public ?price $price;
    public ?coupon $coupon;

    public function __construct(?user_tier $tier, ?price $price, ?coupon $coupon = null){
        $this->tier = $tier;
        $this->price = $price;
        $this->coupon = $coupon;
    }

    public static function create($record){
        $tier = user_tier::get_from_multirecord($record, [
            'id' => 'tierid',
        ]);

        if (!empty($record->id)){
            $price = price::get_from_multirecord($record);
        } else {
            $price = new price();
        }

        $coupon = null;
        if (!empty($record->couponid)){
            $coupon = coupon::get_from_multirecord($record, [
                'id'      => 'couponid',
                'owner'   => 'c_owner',
                'enabled' => 'c_enabled',
                'name'    => 'c_name',
            ]);
        }

        return new static($tier, $price, $coupon);
    }
}