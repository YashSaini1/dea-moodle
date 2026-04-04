<?php

namespace auth_stripe\model\dto;

require_once($CFG->dirroot.'/auth/stripe/vendor/autoload.php');

use Stripe\Invoice;
use Stripe\Subscription;

class stripe_payment_info {

    public ?Subscription $subscription;

    public ?Invoice $invoice;

    /**
     * @param Subscription|null $subscription
     * @param Invoice|null      $invoice
     */
    public function __construct(Subscription $subscription = null, Invoice $invoice = null){
        $this->subscription = $subscription;
        $this->invoice = $invoice;
    }
}