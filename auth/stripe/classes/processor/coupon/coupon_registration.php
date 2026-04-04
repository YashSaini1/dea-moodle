<?php

namespace auth_stripe\processor\coupon;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\processor\stripe_moodle_processor;
use local_sql\core\model\base_object;

class coupon_registration extends stripe_moodle_processor {

    /**
     * @param string $stripeid
     *
     * @return \Stripe\Coupon
     */
    public function load_from_stripe($stripeid): \Stripe\StripeObject{
        return $this->_stripe->retrieve_coupon($stripeid);
    }

    /**
     * @param string $stripeid
     *
     * @return coupon
     */
    public function load_from_moodle($stripeid): ?base_object{
        return coupon::get(['stripeid' => $stripeid]);
    }

    public function register($stripeid){
        if ($this->load_from_moodle($stripeid)){
            // LOG HERE
            return core::str('coupon:error:already_registered');
        }

        try {
            $stripe_coupon = $this->load_from_stripe($stripeid);
        } catch (\Stripe\Exception\ApiErrorException $e){
            core::error('Coupon retrieve error: '.$e->getMessage());
            return $e->getMessage();
        }

        $current_coupon = coupon::get(['name' => $stripe_coupon->name]);
        if ($current_coupon){
            return core::str('coupon:error:already_registered_with_such_name', $current_coupon->name);
        }

        // validation stub
        $result = $this->process_registration($stripe_coupon);
        return $result ? '' : core::str('error:undefined');
    }

    /**
     * @param \Stripe\Coupon $stripeObject
     *
     * @return base_object|bool
     */
    public function process_registration($stripeObject){
        $coupon_durations = [
            coupon::DURATION_ONCE      => true,
            coupon::DURATION_REPEATING => true,
            coupon::DURATION_FOREVER   => true,
        ];

        if (!$coupon_durations[$stripeObject->duration]){
            throw new \moodle_exception('coupon:error:undefined_duration', core::PLUGIN_NAME, '', $stripeObject->duration);
        }

        try {
            $coupon = coupon::create([
                'name'               => $stripeObject->name,
                'stripeid'           => $stripeObject->id,
                'amount_off'         => $stripeObject->amount_off,
                'percent_off'        => $stripeObject->percent_off ? (int) round($stripeObject->percent_off * 100) : null,
                'currency'           => $stripeObject->currency,
                'duration'           => $stripeObject->duration,
                'duration_in_months' => $stripeObject->duration_in_months ?? 0, // null if duration != 'repeating'
                'enabled'            => 1,
            ]);
        } catch (\Throwable $e){
            core::error('Coupon creation '.$e->getMessage());
            return false;
        }

        return $coupon;
    }
}