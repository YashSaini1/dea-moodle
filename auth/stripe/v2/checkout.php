<?php

require('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');

use auth_stripe\model\coupon;
use auth_stripe\model\product;
use auth_stripe\model\price\price_description;
use auth_stripe\processor\coupon\coupon_validator;
use auth_stripe\stripe;
use auth_stripe\StripeAPI;
use auth_stripe\model\customer;
use auth_stripe\model\checkout;
use local_referral\core\referrals\referrals_manager;

$secret     = optional_param('secret', 'empty', PARAM_TEXT);
$set_coupon     = optional_param('coupon', null, PARAM_TEXT);

require_login();

$stripeAPI = new StripeAPI();

try {
    $price = get_price_by_token($secret);

    if (!$price || !$price->is_checkout) {
        throw new \moodle_exception('stripe_error', 'auth_stripe', '', 'Price not found');
    }

    $desc = price_description::get_by_price($price);

    $success_url = new moodle_url('/auth/stripe/payment/success.php');
    $cancel_url = new moodle_url('/user/profile.php');

    $customer = customer::get_by_email($USER->email);

    $customerid = $customer->customerid ?? null;

    if (empty($customerid)) {
        $stripe = new stripe($USER, true);
        $id = $stripe->create_customer($USER);
        $customerid = customer::get(['id' => $id])->customerid;
    }

    $coupon_validator = new coupon_validator();

    $coupon = coupon::get(['stripeid' => $set_coupon ?? null]);

    $coupon = $coupon_validator->validate($coupon->name ?? null, $USER);

    $is_allow_coupon = !$set_coupon && $price->is_allow_coupon;

    if (referrals_manager::is_user_bonus_allow($USER->id) && empty($set_coupon)) {
        $ref_product = product::get_by_id($price->productid);
        $set_coupon = referrals_manager::get_coupon(true, $ref_product->is_coaching_page());
        $is_allow_coupon = false;
    }


    $imageurl = (new moodle_url('/theme/sql/pix/logo_mobile.svg'))->out();

    $checkout = $stripeAPI->get_checkout(
        $customerid,
        $success_url,
        $cancel_url,
        $price->priceid,
        $price->plan_name,
        $desc->info ?? null,
        $is_allow_coupon,
        $set_coupon,
        ($price->period == 'one_time'),
        $imageurl,
        ['v2' => true, 'coupon' => $set_coupon],
    );

    checkout::create(['userid' => $USER->id, 'checkoutid' => $checkout->id, 'priceid' => $price->id, 'timecreated' => time()]);

} catch (\Exception $e) {
    throw new \moodle_exception('stripe_error', 'auth_stripe', '', $e->getMessage());
}

redirect($checkout->url);
die;