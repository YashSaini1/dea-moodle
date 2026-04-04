<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class coupon extends base_object {

    static protected string $table = stripe_database::TABLE_COUPON;

    const PAGE = '/auth/stripe/admin/coupons.php';

    const DURATION_ONCE = 'once';
    const DURATION_REPEATING = 'repeating';
    const DURATION_FOREVER = 'forever';

    public $id;
    public $name;
    public $stripeid;
    public $amount_off;
    public $percent_off;
    public $currency;
    public $duration;
    public $duration_in_months;
    public $owner;
    public $enabled;

    public function save(){
        if (empty($this->id)){
            $this->owner = core::get_userid();
        }

        parent::save();
    }

    /**
     * @param string $stripe_couponid
     *
     * @return coupon|null
     */
    public static function get_by_stipeid($stripe_couponid): ?coupon{
        if (empty($stripe_couponid)){
            return null;
        }

        return static::get(['stripeid' => $stripe_couponid]);
    }

    public static function get_by_name($stripe_coupon): ?coupon{
        if (empty($stripe_coupon)){
            return null;
        }

        return static::get(['name' => $stripe_coupon]);
    }
}