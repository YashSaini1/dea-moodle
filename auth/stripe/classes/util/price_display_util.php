<?php

namespace auth_stripe\util;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\model\price;
use auth_stripe\subscription\tier_price_entity;

/**
 * Utility class for price rendering and displaying
 */
class price_display_util {

    /**
     * Format price number
     *
     * @param int|float $price
     *
     * @return string
     */
    public static function format_price($price){
        // Hack for calculated prices. They may be 300.00000000014 and will be displayed as 300.00
        $price = round($price, 4);
        if (util::has_demical($price)){
            return number_format($price, 2);
        }
        return number_format($price);
    }

    /**
     * @param price            $price
     * @param price|false|null $dependent_price
     * @param                  $coupon
     *
     * @return string
     */
    public static function format_profile_price(price $price, $dependent_price = false, $coupon = null){
        return !empty($price->id) ? static::format_displayed_price($price, $dependent_price, $coupon) : null;
    }

    /**
     * @param tier_price_entity $tier_entity
     * @param price|false|null  $dependent_price
     *
     * @return array
     */
    public static function format_profile_price_with_coupon(tier_price_entity $tier_entity, $dependent_price = false){
        $coupon_price = null;
        if (!empty($tier_entity->coupon) && $tier_entity->coupon->duration != coupon::DURATION_ONCE){
            $coupon_price = static::format_profile_price($tier_entity->price, $dependent_price, $tier_entity->coupon);
        }
        $formatted_price = static::format_profile_price($tier_entity->price, $dependent_price);
        return [$formatted_price, $coupon_price];
    }

    /**
     * @param price            $price
     * @param price|false|null $dependent_price {@see price_display_util::format_price_sum}
     * @param                  $coupon
     *
     * @return string
     */
    public static function format_displayed_price(price $price, $dependent_price = false, $coupon = null){
        $price_sum = static::format_price_sum($price, $dependent_price, $coupon);

        if (!empty($dependent_price)){
            return static::format_price($price_sum);
        }

        $result = static::format_price($price_sum);
        if ($price->period == core::PERIOD_ONE_TIME){
            return $result;
        }

        // if limited subscription
        if ($price->max_times > 0){
            return static::format_price($price->price).'/'.$price_sum;
        }

        if ($price->period == core::PERIOD_YEAR){
            return $result.'/yr';
        } elseif ($price->period == core::PERIOD_MONTH) {
            return $result.'/mo';
        } elseif ($price->period == core::PERIOD_DAY) {
            return $result.'/day';
        }

        return $result;
    }

    /**
     * @param price            $price
     * @param price|false|null $dependent_price   - if price object - it will render
     *                                            if false - we should find dependent price
     *                                            if null - render only first price
     * @param coupon|null      $coupon
     *
     * @return float|int|mixed
     */
    public static function format_price_sum(price $price, $dependent_price = false, $coupon = null){
        $apply_coupon = function($sum) use ($coupon){
            if (!$coupon){
                return $sum;
            }

            if (!empty($coupon->percent_off)){
                return $sum * (1 - $coupon->percent_off / 10000); // 10000 is because percent 16.99 stored as (int) 1699
            }
            if (!empty($coupon->amount_off)){
                $result = $sum - ($coupon->amount_off / 100);
                return $result > 0 ? $result : 0;
            }
            return $sum;
        };

        if ($dependent_price === false){
            $dependent_price = $price->get_price_dependency();
        }

        $price_sum = $price->price;
        if (!empty($dependent_price)){
            $dependency_price = $dependent_price->price * $dependent_price->max_times;
            return $apply_coupon($price_sum + $dependency_price);
        }

        if ($price->period == core::PERIOD_ONE_TIME){
            return $apply_coupon($price_sum);
        }

        // if limited subscription
        if ($price->max_times > 0){
            return $apply_coupon($price_sum * $price->max_times);
        }

        return $apply_coupon($price_sum);
    }
}