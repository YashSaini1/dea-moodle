<?php

namespace auth_stripe\processor;

use auth_stripe\stripe;
use local_sql\core\model\base_object;
use Stripe\StripeObject;

abstract class stripe_moodle_processor {

    protected stripe $_stripe;

    public function __construct($stripe = null){
        $this->_stripe = $stripe ?? new stripe(null, false);
    }

    abstract public function register($stripeid);

    /**
     * @param string $stripeid
     *
     * @return base_object
     */
    abstract public function load_from_moodle($stripeid): ?base_object;

    /**
     * @param string $stripeid
     *
     * @return StripeObject
     */
    abstract public function load_from_stripe($stripeid): StripeObject;

    /**
     * @param StripeObject $stripeObject
     *
     * @return base_object|bool
     */
    abstract public function process_registration($stripeObject);
}