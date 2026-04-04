<?php

namespace auth_stripe\model;

use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class user_tier_price extends base_object {

    static protected string $table = stripe_database::TABLE_USER_TIER_PRICE;

    public $id;
    public $usertierid;
    public $priceid; // local price id
    public $stripepriceid;
    public $couponid; // local coupon id

    public static function delete_by_tier($tierid){
        global $DB;
        $DB->delete_records(static::$table, ['usertierid' => $tierid]);
    }

    public static function delete_by_price($priceid){
        global $DB;
        $DB->set_field(static::$table, 'priceid', '0', ['priceid' => $priceid]);
    }
}