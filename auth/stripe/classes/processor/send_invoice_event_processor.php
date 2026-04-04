<?php

namespace auth_stripe\processor;

use auth_stripe\event\payment_created;
use auth_stripe\task\send_invoice_email;
use Stripe\Invoice;
use Stripe\Subscription;

class send_invoice_event_processor {

    protected payment_created $event;

    /**
     * @param payment_created $event
     */
    public function __construct(payment_created $event){
        $this->event = $event;
    }

    /**
     * Main process event action
     */
    public function process_event(){
        if (!$this->_validate_event()){
            return;
        }

        $stripe_info = $this->event->stripe_payment_info;
        if (empty($stripe_info)){
            $this->_find_invoice_and_send();
            return;
        }

        if (!empty($stripe_info->invoice)){
            $this->_process_invoice($stripe_info->invoice);
            return;
        }

        if (!empty($stripe_info->subscription)){
            $this->_process_subscription($stripe_info->subscription);
            return;
        }

        $this->_find_invoice_and_send();
    }


    /// VALIDATION BLOCK
    /// This code should be overwrited via validator class

    /**
     * Validate event data
     *
     * @return bool
     */
    protected function _validate_event(): bool{
        if (empty($this->event)){
            return false;
        }

        $event = $this->event;
        if (!$event->product->is_coaching_page()){
            return false;
        }

        if (!empty($event->stripe_payment_info->subscription)){
            return $this->_validate_subscription($event->stripe_payment_info->subscription);
        }

        if (!empty($event->stripe_payment_info->invoice)){
            return $this->_validate_invoice($event->stripe_payment_info->invoice);
        }

        return true;
    }

    /**
     * @param Subscription $subscription
     *
     * @return bool
     */
    protected function _validate_subscription(Subscription $subscription): bool{
        return $subscription->status == Subscription::STATUS_ACTIVE && $this->_validate_invoice($subscription->latest_invoice);
    }

    /**
     * @param Invoice $invoice
     *
     * @return bool
     */
    protected function _validate_invoice(Invoice $invoice): bool{
        return $invoice->status == Invoice::STATUS_PAID;
    }

    /// VALIDATION BLOCK END

    protected function _default_data(): array{
        return [
            'product' => $this->event->product,
            'price'   => $this->event->price,
            'userid'  => $this->event->userid,
        ];
    }

    /**
     * @param Invoice $invoice
     *
     * @return void
     */
    public function _process_invoice($invoice){
        $data = $this->_default_data();
        $data[send_invoice_email::PARAM_INVOICE] = $invoice->id;
        $this->_create_task($data);
    }

    /**
     * @param Subscription $subscription
     *
     * @return void
     */
    protected function _process_subscription($subscription){
        $this->_process_invoice($subscription->latest_invoice);
    }

    /**
     * If no invoice provided, we should try to find it via stripe and send the email
     */
    protected function _find_invoice_and_send(){
        $data = $this->_default_data();
        $data[send_invoice_email::PARAM_FIND_INVOICE] = true;
        $this->_create_task($data);
    }

    /**
     * Create adhoc task to send invoice
     *
     * @param array $params
     */
    protected function _create_task($params){
        $task = new send_invoice_email();
        $task->set_custom_data($params);
        \core\task\manager::queue_adhoc_task($task);
    }
}