<?php

namespace auth_stripe\model\price;

use auth_stripe\core;
use auth_stripe\model\price;
use local_sql\core\model\base_object;

abstract class price_model extends base_object {

    /**
     * @param price|int $price_or_id
     *
     * @return static|null
     */
    public static function get_by_price($price_or_id, $sort = ''){
        $priceid = core::get_id($price_or_id);
        if ($priceid < 1){
            return null;
        }
        return parent::get(compact('priceid'), $sort);
    }

    /**
     * @param int $priceid
     */
    public static function delete_by_price($priceid){
        global $DB;
        $DB->get_records(static::table(), ['priceid' => $priceid]);
    }
}