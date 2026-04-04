<?php

namespace auth_stripe\util;

use auth_stripe\core;
use auth_stripe\model\price;
use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_price_loader;

class util {

    public static function format_premium_period(price $price){
        if (empty($price->id)){
            return null;
        }

        if ($price->period == core::PERIOD_DAY){
            return 'daily';
        } elseif ($price->period == core::PERIOD_MONTH) {
            return 'monthly';
        } elseif ($price->period == core::PERIOD_YEAR) {
            return 'annually';
        }

        if ($price->period == core::PERIOD_ONE_TIME){
            return 'one-time';
        }

        return '"-"';
    }

    public static function format_coaching_period(price $price){
        if ($price->period == core::PERIOD_ONE_TIME){
            return '1 time';
        }

        if ($price->period == core::PERIOD_DAY){
            return '1 day';
        } elseif ($price->period == core::PERIOD_MONTH) {
            return '30 days';
        } elseif ($price->period == core::PERIOD_YEAR) {
            return '12 months';
        }

        return '"-"';
    }

    public static function has_demical($numeric): bool{
        return (int)$numeric != $numeric;
    }

    public static function format_date($timestamp){
        return date('F d, Y', $timestamp);
    }

    /**
     * @param user_tier $tier
     * @param price|null $price
     *
     * @return string
     */
    public static function get_tier_shortname(user_tier $tier, price $price = null){
        if ($tier->is_coaching()){
            return core::TIER_COACHING;
        }

        if ($tier->is_premium()){
            if (empty($price)){
                $price = tier_price_loader::get_entity($tier)->price;
            }
            if (!empty($price) && $price->period == core::PERIOD_YEAR){
                return core::TIER_ANNUAL_PREMIUM;
            } else {
                return core::TIER_PREMIUM;
            }
        }

        return core::TIER_FREE;
    }
}