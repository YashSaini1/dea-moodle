<?php

/**
 * Stripe webhook handler
 *
 * @package     auth_stripe
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_stripe\core;
use auth_stripe\core\v2\stripev2;
use Stripe\Event;
use Stripe\Invoice;

require_once('../../config.php');
require_once($CFG->dirroot.'/auth/stripe/webhooklib.php');

// If you are testing your webhook locally with the Stripe CLI you
// can find the endpoint's secret by running `stripe listen`
// Otherwise, find your endpoint's secret in your webhook settings in the Developer Dashboard
$config = get_config('auth_stripe');
$webhook_endpoint_key = $config->webhook_endpoint_key;

$logger = core::get_logger(core::PLUGIN_PATH);

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_endpoint_key);
} catch (\UnexpectedValueException $e){
    // Invalid payload
    $logger::error('error - Invalid payload'."\n");
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e){
    // Invalid signature
    http_response_code(200);
    $logger::error('error - Invalid signature'."\n");
    exit();
}

//$log_file = 'stripe_webhook.log';
//$event_data = json_encode($event, JSON_PRETTY_PRINT);
//file_put_contents($log_file, $event_data . PHP_EOL, FILE_APPEND);

$global_data = $event->data->object;
if (isset($global_data->metadata->v2)) {
    stripev2::processing($event);
    die;
}

// Handle the event
try {
    switch ($event->type){
        case Event::CUSTOMER_SUBSCRIPTION_UPDATED:
            /** @var $subscription \Stripe\Subscription */
            $subscription = $event->data->object;
            $message = webhook_subscription_updated($subscription);
            break;
        case Event::CUSTOMER_SUBSCRIPTION_DELETED:
            /** @var $subscription \Stripe\Subscription */
            $subscription = $event->data->object;
            $message = webhook_subscription_deleted($subscription);
            break;
        case Event::INVOICE_PAYMENT_SUCCEEDED:
        case Event::INVOICE_PAYMENT_FAILED:
        case Event::INVOICE_DELETED:
            /** @var Invoice $invoice */
            $invoice = $event->data->object;
            $message = webhook_invoice($invoice, $event->type == Event::INVOICE_PAYMENT_SUCCEEDED);
            break;
    }
} catch (Throwable $e) {
    \auth_stripe\core::log_message('[ERROR WEBHOOK] '.$e->getMessage());
}
http_response_code(200);