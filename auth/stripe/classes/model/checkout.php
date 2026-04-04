<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class checkout extends base_object {

    static protected string $table = stripe_database::TABLE_CHECKOUT;

    public $id;
    public $userid;
    public $checkoutid;
    public $priceid;
    public $timecreated;

    public static function get_by_checkoutid($checkoutid): ?checkout{
        if (empty($checkoutid))
            return null;

        return static::get(['checkoutid' => $checkoutid]);
    }
}