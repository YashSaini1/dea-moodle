<?php

use auth_stripe\core;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\processor\coupon\coupon_validator;
use auth_stripe\stripe;
use auth_stripe\subscription\tier_processor;
use local_referral\core\referrals\referrals_manager;

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');

\auth_stripe\core::track_time('Payment');

$priceid = optional_param('price', '-1', PARAM_TEXT);
$email = optional_param('email', '', PARAM_TEXT);
$payment_method = optional_param('payment_method', '', PARAM_TEXT);
$coupon_name = optional_param('coupon', '', PARAM_TEXT);

$email = trim($email);
$PAGE->set_context(context_system::instance());
$page_type = stripe::get_page();
$price = price::get_by_id($priceid);
$result = [
    'status' => 'ok',
];
$send_result = function($additional_data = []) use (&$result){
    $printed_data = array_merge($additional_data, $result);
    \auth_stripe\core::info('RESULT - '.print_r($printed_data, 1));
    \auth_stripe\core::track_time('Payment', 1);
    die(json_encode($result));
};

\auth_stripe\core::info('PAYMENT DATA - '.print_r([
        'email'   => $email,
        'price'   => $price->id,
        'product' => $price->productid ?? '-',
        'coupon'  => $coupon_name,
        'userid'  => $USER->id,
    ], 1));

if (empty($price) || !$price->enabled){
    $result = [
        'status'  => 'error',
        'message' => 'You provide wrong price id',
    ];
    $send_result([
        'price'              => $price,
        'additional_message' => 'Price can be disabled',
    ]);
}

$product = product::get_by_id($price->productid);
if (empty($product)){
    $result = [
        'status'  => 'error',
        'message' => 'Incorrect product!',
    ];
    $send_result();
}
if ($product->sql_page != $page_type){
    $result = [
        'status'  => 'error',
        'message' => 'Invalid session! Please refresh page',
    ];
    $send_result(['product_type' => $product->sql_page ?? '-', 'user_page' => $page_type ?? '-']);
}

if (isset($SESSION->sending_pay_to_stripe) && (($SESSION->sending_pay_to_stripe) + 10 >= time())){
    $result = [
        'status'  => 'error',
        'message' => 'manyrequest',
    ];
    $send_result();
}

if (!empty($USER->id) && $email == $USER->email){
    $userobj = $USER;
} elseif (!empty($email)) {
    $userobj = \auth_stripe\core::get_user_by_email($email);
} else {
    $result = [
        'status'  => 'error',
        'message' => 'Email field is required!',
    ];
    $send_result(['email' => $email ?? '-', 'user_email' => $USER->email ?? '-']);
}

/// COUPON
if (!empty($coupon_name)){
    $coupon_validator = new coupon_validator();
    $coupon = $coupon_validator->validate($coupon_name, $userobj);
    if (empty($coupon)){
        $result = [
            'status'  => 'error',
            'message' => core::str('coupon:error:invalid_coupon'),
        ];
        $send_result();
    }

    stripe::set_coupon($coupon->stripeid);
}

$is_coaching = core::is_coaching_page($page_type);
// Check if user already paid for this product
// Allow to create multiple payments for the second coaching
if ($page_type != core::SECOND_COACHING_PAGE && !empty($userobj) && tier_processor::check_page($page_type, $userobj)){
    if (!empty($USER->email)){
        $url = $CFG->wwwroot.'/user/profile.php';
    } else {
        $url = $CFG->wwwroot.'/auth/stripe/payment/success.php';
        if ($is_coaching){
            $SESSION->success_page = true;
        }
        sleep(3); // modeling real query, because user is not logged in
    }
    $result['redirect_url'] = $url;
    $send_result();
}

\auth_stripe\core::track_time('Create user');
if (empty($userobj)){
    if ($page_type == core::MAIN_PAGE){
        $result = [
            'status'  => 'error',
            'message' => 'logged',
        ];
        $send_result();
    }
    $SESSION->price = $price;
    try {
        $userobj = create_new_user_from_email($email);
    } catch (Throwable $e){
        \auth_stripe\core::error('PAYMENT CREATE USER - '.$e->getMessage().' '.$e->getTraceAsString());
        $result = [
            'status'  => 'error',
            'message' => 'Cannot create user',
        ];
        $send_result();
    }
    unset($SESSION->price);
    $SESSION->new_user_mail = $email;
}
\auth_stripe\core::track_time('Create user', 1);
\auth_stripe\core::info('PAYMENT EMAIL - '.$email, ['userid' => $userobj->id]);

$SESSION->sending_pay_to_stripe = time();
$stripe = new stripe($userobj, true);
try {
    // TODO update this logic.
    //  needs to get moodle customer and add he to the stripe if not exits on stripe
    \auth_stripe\core::track_time('Create customer');
    $stripe->create_customer($userobj);
    \auth_stripe\core::track_time('Create customer', 1);

    \auth_stripe\core::track_time('Attach payment method');
    $stripe->update_payment_method($payment_method);
    \auth_stripe\core::track_time('Attach payment method', 1);

    \auth_stripe\core::track_time('Start all payments');
    $payed_successfully = $stripe->buy_product($product, $price);
    \auth_stripe\core::track_time('Start all payments', 1);

    if ($payed_successfully) {
        \auth_stripe\core::track_time('[REFERRAL] Processing...');
        new referrals_manager($userobj, $price, $coupon_name);
        \auth_stripe\core::track_time('[REFERRAL] Processing...', 1);
    }

    if ($payed_successfully && core::is_coaching_page($page_type)){
        $adhocktask = new \auth_stripe\task\send_seller_email();
        $price->price *= 100;
        $adhocktask->set_custom_data([
            'userid'  => $userobj->id,
            'product' => $product,
            'price'   => $price,
        ]);
        \core\task\manager::queue_adhoc_task($adhocktask);
        $price->price /= 100;
    }
    \auth_stripe\stripe::clear_page();
} catch (Throwable $e){
    \auth_stripe\core::error('PAYMENT ERROR - '.$e->getMessage());
    $result['status'] = 'error';
    $result['message'] = $e->getMessage();
    $send_result();
}
unset($SESSION->sending_pay_to_stripe);
$SESSION->payment_info = [
    'product'      => $product->name,
    'price_sum'    => \auth_stripe\util\price_display_util::format_price_sum($price, false, $coupon ?? null),
    'price_name'   => $price->plan_name,
    'price_period' => $price->period,
    'currency'     => $price->currency,
];

$result['redirect_url'] = '';

$token = price::get_token_by_price_id($price->id);

if (!$payed_successfully) {
    $result['status'] = 'error';
    $result['message'] = 'Something went wrong. Try again later.';
} elseif ($token == 'wTHTXzNubpw4TPF') { // HARDCODE
    $result['redirect_url'] = 'http://www.dea2.com/diamondit24xbuu';
} elseif ($is_coaching || $page_type == core::SPECIAL_PREMIUM_PAGE) {
    $result['redirect_url'] = $CFG->wwwroot.'/auth/stripe/payment/success.php';
    $SESSION->success_page = true;
} elseif (!empty($result)) {
    \core\notification::success(core::str('payment_successfully'));
    $result['redirect_url'] = $CFG->wwwroot.'/user/profile.php';
}
$send_result();