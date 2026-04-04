<?php

/**
 * Stripe webhook lib.
 * Contains all webhook processors.
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_stripe\core\stripe_database;
use auth_stripe\model\customer;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\model\send_invoices;
use auth_stripe\model\user_tier;
use auth_stripe\stripe;
use auth_stripe\subscription\tier_price_loader;
use auth_stripe\subscription_processor;
use Stripe\Invoice;

require_once($CFG->dirroot.'/auth/stripe/lib.php');
require_once($CFG->dirroot.'/auth/stripe/vendor/autoload.php');

/**
 * @param \Stripe\Subscription $subscription
 *
 * @return string
 */
function webhook_subscription_updated($subscription){
    global $DB;
    $message = '';
    if ($subscription->collection_method != 'charge_automatically'){
        return 'Subscription is not charged automatically';
    }

    $customer_id = $subscription->customer;

    $customer = customer::get(['customerid' => $customer_id]);
    if (!$customer){
        return 'No customer "'.$customer_id.'"';
    }

    $user = \core_user::get_user($customer->userid);
    if (in_array($subscription->status, ['past_due', 'unpaid', 'canceled'])){
        $stripe = new stripe($user);
        $processor = new subscription_processor($user, null, $stripe);
        if ($subscription->status == 'canceled'){
            $message = $processor->delete_local_subscription(null, $subscription);
        } else {
            $message = $processor->delete_local_subscription(null, $subscription, 1);
            $stripe->cancel_subscription($subscription->id);
        }
        return 'Subscription status is "'.$subscription->status.'". Delete. '.$message;
    }

    $tier_entity = tier_price_loader::get_entity_by_user_stripe_subscription($customer->userid, $subscription);
    if (empty($tier_entity) || empty($tier_entity->tier)){
        return 'Nothing to update';
    }

    [$tier, $price] = [$tier_entity->tier, $tier_entity->price];
    $last_invoice = $DB->get_record(stripe_database::TABLE_INVOICE, ['customer_id' => $customer->id]);
    if ($last_invoice->invoice_id != $subscription->latest_invoice){
        $stripe = new stripe($user);

        $invoice = $stripe->retrieve_invoice($subscription->latest_invoice);
        if ($invoice->status != Invoice::STATUS_PAID){
            send_invoices::create([
                'userid'    => $user->id,
                'invoiceid' => $invoice->id,
                'priceid'   => $price->id,
                'productid' => $price->productid,
            ]);
            $message = 'Waiting for paid invoice. ';
        } else {
            $stripe_price = $stripe->get_price($price->priceid);
            $price->price = $stripe_price->unit_amount / 100;

            $product = user_tier::get_product($tier->tier);
            $eventdata = ['userid' => $user->id, 'context' => \context_system::instance()];
            $stripe_info = new \auth_stripe\model\dto\stripe_payment_info(null, $invoice);

            $event = \auth_stripe\event\payment_created::create_by_product($product, $price, $eventdata, $stripe_info);
            $event->trigger();
            $message = 'Trigger payment event. ';
        }
    }

    $current_period_end = $subscription->current_period_end;
    if ($subscription->cancel_at){
        $current_period_end = $subscription->cancel_at;
    }

    $tier->current_period_end = $current_period_end;
    $tier->current_period_start = $subscription->current_period_start;
    $tier->save();
    // \core\session\manager::kill_user_sessions($customer->userid);
    return $message.'Updated subscription';
}

/**
 * @param \Stripe\Subscription $subscription
 *
 * @return string
 */
function webhook_subscription_deleted($subscription){
    $customer_id = $subscription->customer;
    $customer = customer::get(['customerid' => $customer_id]);
    if (empty($customer)){
        return 'No customer '.$customer_id;
    }

    $user = \core_user::get_user($customer->userid);
    $stripe = new stripe($user);
    $processor = new subscription_processor($user, null, $stripe);
    return $processor->delete_local_subscription(null, $subscription);
}

function webhook_invoice(Invoice $invoice, $success){
    $moodle_record = send_invoices::get_by_invoiceid($invoice->id);
    if (empty($moodle_record)){
        return 'Undefined invoice '.$invoice->id.'. Skip.';
    }
    if ($invoice->status == Invoice::STATUS_PAID){
        $price = price::get_by_id($moodle_record->priceid);
        $product = product::get_by_id($moodle_record->productid);

        $eventdata = ['userid' => $moodle_record->userid, 'context' => \context_system::instance()];
        $stripe_info = new \auth_stripe\model\dto\stripe_payment_info(null, $invoice);

        $event = \auth_stripe\event\payment_created::create_by_product($product, $price, $eventdata, $stripe_info);
        $event->trigger();
        $moodle_record->delete();
        return 'Trigger payment event for invoice';
    }

    if (!$success){
        $moodle_record->delete();
        return 'Delete local invoice record';
    }

    return 'Nothing to do';
}

