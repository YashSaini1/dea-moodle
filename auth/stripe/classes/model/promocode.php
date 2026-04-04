<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class promocode extends base_object {

    static protected string $table = stripe_database::TABLE_PROMOCODE;

    const PAGE = '/auth/stripe/admin/promocodes.php';

    public $id;
    public $code;
    public $couponid;
    public $stripeid;
    public $owner;
    public $enabled;

    public function save(){
        if (empty($this->id)){
            $this->owner = core::get_userid();
        }

        parent::save();
    }
}