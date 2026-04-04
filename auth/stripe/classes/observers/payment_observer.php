<?php

namespace auth_stripe\observers;

use auth_stripe\event\payment_created;
use auth_stripe\processor\send_invoice_event_processor;

class payment_observer {

    public static function payment_created(payment_created $event){
        $processor = new send_invoice_event_processor($event);
        $processor->process_event();
    }
}