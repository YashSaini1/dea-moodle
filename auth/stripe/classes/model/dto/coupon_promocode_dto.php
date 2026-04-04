<?php

namespace auth_stripe\model\dto;

use auth_stripe\model\coupon;
use auth_stripe\model\promocode;

class coupon_promocode_dto {

    public ?promocode $promocode;
    public ?coupon $coupon;

    public function __construct(?coupon $coupon, ?promocode $promocode){
        $this->coupon = $coupon;
        $this->promocode = $promocode;
    }

    public static function create($record){
        //  c.*, c.stripeid as coupon_stripeid, c.owner as coupon_owner, c.enabled as coupon_enabled, p.*
        $coupon = coupon::get_from_multirecord($record, [
            'id'       => 'couponid',
            'stripeid' => 'coupon_stripeid',
            'owner'    => 'coupon_owner',
            'enabled'  => 'coupon_enabled',
        ]);

        if (!empty($record->id)){
            $promocode = promocode::get_from_multirecord($record);
        } else {
            $promocode = new promocode();
        }

        return new static($coupon, $promocode);
    }
}