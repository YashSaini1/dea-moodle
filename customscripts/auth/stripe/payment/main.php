<?php

/**
 * User premium AB testing page
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use auth_stripe\subscription\tier_processor;
use local_ab_testing\test\premium_price_test;
use local_ab_testing\test\premium_price_annual_test;

if (!get_config('local_ab_testing', 'enable') || !premium_price_annual_test::is_enabled()){
    return;
}

$coupon = optional_param('apply_coupon', false, PARAM_TEXT);

$url_params = [];
if (!empty($coupon)){
    $url_params['apply_coupon'] = $coupon;
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(MAIN_PAYMENT_URL, $url_params);
premium_price_annual_test::check_page(MAIN_PAYMENT_URL);

require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');
require_once($CFG->dirroot.'/local/ab_testing/lib.php');

/**
 * {@see get_auth_plugin()} function will trigger an error, if something went wrong
 * @var auth_plugin_stripe $authplugin
 */
$authplugin = get_auth_plugin('stripe');

require_login();

$page_type = \auth_stripe\core::MAIN_PAGE;
if (tier_processor::check_page($page_type) || \local_sql\moodle\role_manager::is_admin()){
    \auth_stripe\core::redirect_to_profile();
}

$PAGE->set_title(\auth_stripe\core::str('payment_title'));
$PAGE->set_heading($SITE->fullname);

\local_ab_testing\stripe\pages\premium_price::payment_page($authplugin, $page_type, '', $coupon);
//\local_ab_testing\stripe\pages\premium_description::payment_page($authplugin, $page_type, '', $coupon);