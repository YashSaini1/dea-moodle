<?php

namespace auth_stripe\processor\promocode;

use auth_stripe\core;
use auth_stripe\model\promocode;
use auth_stripe\processor\coupon\coupon_registration;
use auth_stripe\processor\stripe_moodle_processor;
use local_sql\core\model\base_object;

class promocode_registration extends stripe_moodle_processor {

    protected coupon_registration $_coupon_registration;

    public function __construct($stripe = null){
        parent::__construct($stripe);
        $this->_coupon_registration = new coupon_registration($this->_stripe);
    }

    public function register($promocodeid){
        if ($this->load_from_moodle($promocodeid)){
            // LOG HERE
            return core::str('pormocode:error:already_registered');
        }

        try {
            $stripe_promocode = $this->load_from_stripe($promocodeid);
        } catch (\Stripe\Exception\ApiErrorException $e){
            core::error('Promocode retrieve error: '.$e->getMessage());
            return $e->getMessage();
        }
        // validation stub
        $result = $this->process_registration($stripe_promocode);
        return $result ? '' : core::str('error:undefined');
    }

    /**
     * @param string $promocodeid
     *
     * @return \Stripe\PromotionCode
     */
    public function load_from_stripe($promocodeid): \Stripe\StripeObject{
        return $this->_stripe->retrieve_promocode($promocodeid);
    }

    /**
     * @param string $promocodeid
     *
     * @return promocode
     */
    public function load_from_moodle($promocodeid): ?base_object{
        return promocode::get(['stripeid' => $promocodeid]);
    }

    /**
     * @param \Stripe\PromotionCode $stripeObject
     *
     * @return bool|base_object
     */
    public function process_registration($stripeObject){
        try {
            $stripe_coupon = $stripeObject->coupon;
            $coupon = $this->_coupon_registration->load_from_moodle($stripe_coupon->id);
            if (!$coupon){
                $coupon = $this->_coupon_registration->process_registration($stripe_coupon);
                if (!$coupon){
                    return false;
                }
            }
            $promocode = promocode::create([
                'code'     => $stripeObject->code,
                'couponid' => $coupon->id,
                'stripeid' => $stripeObject->id,
                'enabled'  => 1,
            ]);
        } catch (\Throwable $e){
            core::error('Promocode creation '.$e->getMessage());
            return false;
        }

        return $promocode;
    }
}