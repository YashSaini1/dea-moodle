<?php

namespace local_data_collector\observers;

use auth_stripe\event\cancel_tier;
use auth_stripe\event\payment_created;
use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_price_loader;
use local_data_collector\core;
use local_data_collector\moodle_event_dto;
use local_data_collector\webhook_processor;

/**
 * Observer for user events
 *
 * @package     local_data_collector
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class stripe_observer extends data_collector {

    public static function process_event(\core\event\base $event){
        if (!parent::process_event($event)){
            return false;
        }

        switch (get_class($event)){
            case payment_created::class:
                static::_payment_created($event);
                break;
            case cancel_tier::class:
                static::_cancel_tier($event);
                break;
        }
        return true;
    }

    /**
     * @param payment_created $event
     *
     * @return bool
     */
    protected static function _payment_created(payment_created $event){
//        if (!static::_validate_payment_user_tiers($event->user_tiers)){
//            return false;
//        }

        $data = new moodle_event_dto([
            'userid' => $event->userid,
            'type'   => core::EVENT_TYPE_PAYMENT,
            'data'   => [
                'product' => $event->product->id,
                'price'   => $event->price->id,
            ],
        ]);
        webhook_processor::process_moodle_event($data);
        return true;
    }

    /**
     * @param user_tier[] $user_tiers
     */
    protected static function _validate_payment_user_tiers(array $user_tiers){
        $premium_tier = $coaching_tier = null;
        foreach ($user_tiers as $tier){
            if ($tier->is_premium()){
                $premium_tier = $tier;
            } elseif ($tier->is_coaching()) {
                $coaching_tier = $tier;
            }
        }

        if (empty($premium_tier) || empty($coaching_tier)){
            return true;
        }

        // When user buy a coaching tier, he also gets a free premium (trial)
        // If he already has a premium, this subscription will be cancelled
        // We don't need to track both of these situations, only real payments
        return $premium_tier->current_period_start - HOURSECS > $coaching_tier->current_period_start;
    }

    /**
     *
     * @param cancel_tier $event
     *
     * @return bool
     */
    protected static function _cancel_tier(cancel_tier $event){
//        if ($event->user_tier->is_premium()){
//            $user_tiers = user_tier::get_by_user($event->userid);
//            if (!static::_validate_cancelled_user_tiers($user_tiers, $event->user_tier)){
//                return false;
//            }
//        }

        $data = new moodle_event_dto([
            'userid' => $event->userid,
            'type'   => core::EVENT_TYPE_CANCEL_TIER,
            'data'   => [
                'tier_price' => tier_price_loader::get_record($event->user_tier),
            ],
        ]);
        webhook_processor::process_moodle_event($data);
        return true;
    }

    /**
     * @param user_tier[] $user_tiers
     */
    protected static function _validate_cancelled_user_tiers(array $user_tiers, user_tier $premium_tier){
        $coaching_tier = null;
        foreach ($user_tiers as $tier){
            if ($tier->is_coaching()){
                $coaching_tier = $tier;
            }
        }

        if (empty($premium_tier) || empty($coaching_tier)){
            return true;
        }

        // When user buy a coaching tier, he also gets a free premium (trial)
        // If he already has a premium, this subscription will be cancelled
        // We don't need to these cancelling.
        // Return true (is valid) when coaching was bought more than hour ago
        return time() - HOURSECS > $coaching_tier->current_period_start;
    }
}