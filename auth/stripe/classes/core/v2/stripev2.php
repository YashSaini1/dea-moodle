<?php
namespace auth_stripe\core\v2;

use auth_stripe\model\checkout;
use auth_stripe\model\coupon;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\model\customer;
use auth_stripe\stripe;
use auth_stripe\subscription_processor;
use core_user;
use local_referral\core\referrals\referrals_manager;
use Stripe\Event;
use Throwable;

class stripev2
{
    public static function processing($event)
    {

        switch ($event->type){
            case Event::CHECKOUT_SESSION_COMPLETED:
            case Event::CHECKOUT_SESSION_ASYNC_PAYMENT_SUCCEEDED:
                $session = $event->data->object;
                self::success_payment($session);
                break;
        }
        http_response_code(200);
    }

    public static function success_payment($session): bool
    {
        try {
            \auth_stripe\core::track_time('[V2] Success payment');
            \auth_stripe\core::log_message("[V2] checkoutid: ".$session->id);

            $customer_id = $session->customer;

            \auth_stripe\core::log_message("[V2] customer_id: $customer_id");

            $customer = customer::get(['customerid' => $customer_id]);
            if (empty($customer)){
                return false;
            }

            \auth_stripe\core::log_message("[V2] customer: ".print_r($customer, true));

            $checkout = checkout::get_by_checkoutid($session->id);

            \auth_stripe\core::log_message("[V2] checkout: ".print_r($checkout, true));

            $user = core_user::get_user($checkout->userid);

            $stripe = new stripe($user, true);

            $price = price::get(['id' => $checkout->priceid]);

            \auth_stripe\core::log_message("[V2] price: ".print_r($price, true));

            $product = product::get_by_id($price->productid);

            \auth_stripe\core::log_message("[V2] product: ".print_r($product, true));

            $coupon = coupon::get(['stripeid' => $session->metadata->coupon]);

            if (!empty($coupon))
                stripe::set_coupon($coupon->stripeid);

            $is_success = $stripe->checkout_processing($product, $price);

            \auth_stripe\core::log_message("[V2] Is success: $is_success");

            if ($is_success) {
                \auth_stripe\core::track_time('[REFERRAL] Processing...');
                new referrals_manager($user, $price, $session->metadata->coupon ?? null);
                \auth_stripe\core::track_time('[REFERRAL] Processing...', 1);
            }

            \auth_stripe\core::track_time('[V2] Success payment', 1);
        } catch (Throwable $e) {
            \auth_stripe\core::log_message('[V2] [ERROR] '.$e->getMessage());
        }
        return true;
    }
}