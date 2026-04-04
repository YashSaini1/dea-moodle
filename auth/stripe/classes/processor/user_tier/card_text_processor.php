<?php

namespace auth_stripe\processor\user_tier;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\processor\coupon\coupon_price_processor;
use auth_stripe\subscription\tier_price_entity;
use auth_stripe\util\util;

class card_text_processor {

    public tier_price_entity $tier_entity;

    public user_tier_output $tier_output;

    /**
     * @param tier_price_entity $tier_entity
     */
    public function __construct(tier_price_entity $tier_entity, user_tier_output $tier_output){
        $this->tier_entity = $tier_entity;
        $this->tier_output = $tier_output;
    }

    public function get_coupon_text(): string{
        $coupon = $this->tier_entity->coupon;
        if (empty($coupon)){
            return '';
        }

        $duration = $coupon->duration;
        if ($duration == coupon::DURATION_ONCE){
            // Do nothing with one time coupon
            return '';
        }

        $coupon_processor = new coupon_price_processor($coupon);
        $discount_value = $coupon_processor->get_coupon_discount();

        $a = new \stdClass();
        $a->discount = $discount_value;

        if ($duration == coupon::DURATION_FOREVER){
            return core::str('coupon:profile:forever', $a);
        }

        if ($duration == coupon::DURATION_REPEATING){
            $created = $this->tier_entity->tier->time_created;
            $due_date_time = strtotime('+'.$coupon->duration_in_months.' months', $created);
            $a->due_date = util::format_date($due_date_time);
            return core::str('coupon:profile:repeating', $a);
        }

        return '';
    }

    public function get_coaching_text(){
        $a = util::format_date($this->tier_entity->tier->current_period_start ?? 0);
        $coaching_text = core::str('coaching_subscription:start_date', $a);
        if (!empty($this->tier_entity->price->id)){
            $coaching_text .= $this->_get_coaching_price_text();
        }
        return $coaching_text;
    }

    protected function _get_coaching_price_text(){
        $tier_entity = $this->tier_entity;
        $now = $this->tier_output->now;
        if ($now > $tier_entity->tier->current_period_end){
            return '';
        }

        $price = $tier_entity->price;
        $tier = $tier_entity->tier;

        $price_dependency = $price->get_price_dependency();
        if (!empty($price_dependency)){
            // Wrong way to save our time.
            // If we have dependency price, show its information.
            $price = $price_dependency;
        }

        if ($price->period == core::PERIOD_ONE_TIME){
            return '';
        }

        // Only subscriptions here
        if ($price->max_times > 0){
            $new_time = strtotime('+'.$price->max_times.' '.$price->period, $tier->current_period_start);

            // All paid
            if ($new_time < $now){
                return '';
            }

            if ($now > $tier->current_period_start){
                $payment_time = strtotime('+1'.$price->period, $tier->current_period_start);
            } else {
                $payment_time = $tier->current_period_start;
                // Get next payment date
                for ($i = 1; $i < $price->max_times; $i++){
                    $payment_time = strtotime('+'.$i.' '.$price->period, $payment_time);
                    if ($now > $payment_time){
                        break;
                    }
                }
            }

            $a = new \stdClass();
            $a->period = util::format_coaching_period($price);
            $a->nextdate = util::format_date($payment_time);
            if ($price->max_times == 2){
                return core::str('coaching_subscription:second_payment_date', $a);
            }

            return core::str('coaching_subscription:next_payment_date', $a);
        }

        $next_time = strtotime('+1 '.$price->period, $tier->current_period_start);

        $a = new \stdClass();
        $a->period = util::format_coaching_period($tier_entity->price);
        $a->nextdate = util::format_date($next_time);
        return core::str('coaching_subscription:next_payment_date', $a);
    }
}