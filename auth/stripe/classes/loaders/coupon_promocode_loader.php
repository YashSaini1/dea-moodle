<?php

namespace auth_stripe\loaders;

use auth_stripe\model\dto\coupon_promocode_dto;

class coupon_promocode_loader {

    protected static function _get_base_sql(){
        return 'SELECT c.*, c.stripeid as coupon_stripeid, c.owner as coupon_owner, c.enabled as coupon_enabled, p.*
                FROM {auth_stripe_promocode} p
                JOIN {auth_stripe_coupon} c ON c.id = p.couponid';
    }

    /**
     * @param string $promocode
     *
     * @return coupon_promocode_dto|null
     */
    public static function get($promocode){
        if (empty($promocode)){
            return new coupon_promocode_dto(null, null);
        }

        $results = static::get_all($promocode);
        return reset($results);
    }

    /**
     * @param string $promocode
     *
     * @return coupon_promocode_dto[]
     */
    public static function get_all($promocode = null){
        $result = [];
        $recordset = static::_load($promocode);
        foreach ($recordset as $record){
            $result[] = coupon_promocode_dto::create($record);
        }

        $recordset->close();
        return $result;
    }

    public static function get_record_by_promocode($promocode){
        if (empty($promocode)){
            return null;
        }

        $result = [];
        $rs = static::_load($promocode);
        foreach ($rs as $record){
            $result[] = $record;
        }

        $rs->close();
        return reset($result);
    }

    public static function get_records(){
        $result = [];
        $rs = static::_load();
        foreach ($rs as $record){
            $result[] = $record;
        }

        $rs->close();
        return $result;
    }

    /**
     * @param string $promocode
     *
     * @return \moodle_recordset
     */
    protected static function _load($promocode = null){
        global $DB;
        $base_sql = static::_get_base_sql();
        $where = $params = [];
        if (!empty($promocode)){
            $where[] = 'p.code=:code';
            $params['code'] = $promocode;
        }

        if (!empty($where)){
            $base_sql .= PHP_EOL.'WHERE '.implode(' AND ', $where);
        }
        $base_sql .= PHP_EOL.'ORDER BY p.id';

        return $DB->get_recordset_sql($base_sql, $params);
    }
}