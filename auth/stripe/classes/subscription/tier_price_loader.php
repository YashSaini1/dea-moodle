<?php

namespace auth_stripe\subscription;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\model\user_tier;
use auth_stripe\model\user_tier_price;

//TODO: move this to the loaders folder
/**
 * Base class to process moodle subscriptions
 */
class tier_price_loader {

    /**
     * Get all user tiers with prices
     *
     * @param int|\stdClass|null $user_or_id
     *
     * @return tier_price_entity[]
     */
    public static function get_all_by_user($user_or_id = null){
        static $user_tiers = [];
        $userid = \auth_stripe\core::get_userid($user_or_id);
        if (!array_key_exists($userid, $user_tiers)){
            $user_tiers[$userid] = static::get_entities($userid);
        }
        return $user_tiers[$userid];
    }

    public static function get_entities($user_or_id = null, $exclude_free = false){
        $records = static::get_records($user_or_id, $exclude_free);
        if (empty($records)){
            return [];
        }
        $result = [];
        foreach ($records as $record){
            $result[] = tier_price_entity::create($record);
        }
        return $result;
    }

    /**
     * Get all tier entity records by user or tier (only one record)
     *
     * @param int|\stdClass $user_or_id
     * @param bool $exclude_free - if true, remove free tier from output
     *
     * @return array|\stdClass[]
     */
    public static function get_records($user_or_id = null, $exclude_free = false){
        $records = static::_load_records($user_or_id);
        if (empty($records)){
            return [];
        }

        if ($exclude_free){
            foreach ($records as $key => $record){
                if ($record->tier == user_tier::FREE_TIER){
                    unset($records[$key]);
                    return $records;
                }
            }
        }

        return $records;
    }

    /**
     * Get tier price entity object
     *
     * @param user_tier|int $tier_or_id
     *
     * @return tier_price_entity|null
     */
    public static function get_entity($tier_or_id): ?tier_price_entity{
        $record = static::get_record($tier_or_id);
        if (empty($record)){
            return null;
        }

        return tier_price_entity::create($record);
    }

    /**
     * Get tier price entity record

     *
     * @param int|user_tier $tier_or_id
     *
     * @return false|\stdClass|null
     */
    public static function get_record($tier_or_id){
        $records = static::_load_records(null, $tier_or_id);
        if (empty($records)){
            return null;
        }

        return reset($records);
    }

    /**
     * Load tier and price records from database
     *
     * This function cache loaded data into static data, therefore you can use it many times during 1 script
     *
     * @param int|\stdClass|null $user_or_id
     * @param int|user_tier|null      $tier_or_id
     *
     * @return \stdClass[]
     */
    protected static function _load_records($user_or_id = null, $tier_or_id = null){
        static $cached = [];

        $userid = core::get_id($user_or_id);
        $tierid = core::get_id($tier_or_id);

        $cache_key = implode('-', [$userid, $tierid]);
        if ($cache_key == '0-0'){ // empty inputted data
            debugging(__CLASS__.'::'.__FUNCTION__.' - empty inputted $user and $tier objects');
            return [];
        }

        if (!array_key_exists($cache_key, $cached)){
            global $DB;
            $where = $params = [];
            if (!empty($userid)){
                $where[] = 'ut.userid = :userid';
                $params['userid'] = $userid;
            }

            if (!empty($tierid)){
                $where[] = 'ut.id = :tierid';
                $params['tierid'] = $tierid;
            }

            $sql = static::_get_tier_price_sql().
                'WHERE '.implode(' AND ', $where)
                .' GROUP BY ut.id 
                   ORDER BY ut.tier desc, p.id asc';

            $cached[$cache_key] = $DB->get_records_sql($sql, $params);
        }

        return $cached[$cache_key];
    }

    protected static function _get_tier_price_sql(){
        return "SELECT ut.id as tierid, ut.*, c.*, p.*, utp.stripepriceid, c.id as couponid, c.owner as c_owner, c.enabled as c_enabled, c.name as c_name
                FROM {".user_tier::table()."} ut
                LEFT JOIN {".user_tier_price::table()."} utp ON utp.usertierid = ut.id
                LEFT JOIN {".price::table()."} p ON (p.id = utp.priceid OR p.dependency = utp.priceid)
                LEFT JOIN {".coupon::table()."} c ON (c.id = utp.couponid)";
    }

    /**
     * @param user_tier|int $tier
     *
     * @return array{0:product, 1:tier_price_entity}
     */
    public static function get_product_with_price_by_tier($tier_or_id){
        $tier_entity = static::get_entity($tier_or_id);
        if (empty($tier_entity)){
            return [null, null];
        }
        $product = user_tier::get_product($tier_entity->tier->tier);
        return [$product, $tier_entity];
    }

    /**
     * Get tier_entity_record from stripe subscription object
     *
     * @param int|\stdClass        $user_or_id
     * @param \Stripe\Subscription $stripe_subscription
     *
     * @return tier_price_entity|null
     */
    public static function get_entity_by_user_stripe_subscription($user_or_id, \Stripe\Subscription $stripe_subscription): ?tier_price_entity{
        $tier_entity_records = static::get_records($user_or_id, true);

        foreach ($tier_entity_records as $entity_record){
            if (empty($entity_record->priceid) || empty($entity_record->stripepriceid)){
                continue;
            }
            // $entity_record->id is id from auth_stripe_price table
            $price = new price(['id' => $entity_record->id]);
            $depended = $price->get_price_dependency();
            foreach ($stripe_subscription->items as $item){
                if ($entity_record->stripepriceid == $item->price->id){
                    return tier_price_entity::create($entity_record);
                }

                // Check depended price for split payments
                if ($depended && $depended->priceid == $item->price->id){
                    $tier_entity = tier_price_entity::create($entity_record);
                    $tier_entity->price = $depended;
                    return $tier_entity;
                }
            }
        }
        return null;
    }
}