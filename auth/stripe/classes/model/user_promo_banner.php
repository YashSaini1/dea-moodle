<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class user_promo_banner extends base_object {

    static protected string $table = stripe_database::TABLE_USER_PROMO_BANNER;

    const PAGE = '/auth/stripe/admin/promobanner.php';

    const PERIODS = [
        core::PERIOD_HOUR,
        core::PERIOD_DAY,
        core::PERIOD_MONTH,
        core::PERIOD_YEAR,
    ];

    const USER_FIELD = 'user_promo_banner';

    const TYPE_NEW_USER = 'new_user';

    const TYPE_SPECIAL = 'special'; // for special group of users

    public $id;
    public $type;
    public $userid;
    public $timecreated;
    public $timedue;

    public static function get_by_user($user_or_id){
        $userid = core::get_userid($user_or_id, false);
        if (!$userid){
            return null;
        }

        $user_promo = static::get([
            'userid' => $userid,
        ]);
        if (!$user_promo){
            return null;
        }

        if ($user_promo->timedue <= time()){
            $user_promo->delete();
            return null;
        }
        return $user_promo;
    }

    public static function process_from_record($record){
        return static::_create_from_record($record);
    }

    public function type_is($type){
        return $this->type == $type;
    }
}