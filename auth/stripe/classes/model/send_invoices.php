<?php

namespace auth_stripe\model;

use auth_stripe\core\stripe_database;
use local_sql\core\model\base_object;

class send_invoices extends base_object {

    static protected string $table = stripe_database::TABLE_SEND_INVOICES;

    public $id;
    public $invoiceid;
    public $userid;
    public $productid;
    public $priceid;

    public static function get_by_invoiceid($stripe_invoiceid){
        return static::get(['invoiceid' => $stripe_invoiceid]);
    }
}