<?php

namespace auth_stripe\model;

use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class customer extends base_object {

    static protected string $table = stripe_database::TABLE_CUSTOMER;

    public $id;
    public $userid;
    public $username;
    public $email;
    public $customerid;
    public $status_access;

    public static function get_by_userid($userid) {
        return static::get(['userid' => $userid]);
    }
    public static function get_by_email($email) {
        return static::get(['email' => $email]);
    }
}