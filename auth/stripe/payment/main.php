<?php

/**
 * User upgrade subscription page.
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_stripe\subscription\tier_processor;

require('../../../config.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');

/**
 * {@see get_auth_plugin()} function will trigger an error, if something went wrong
 * @var auth_plugin_stripe $authplugin
 */
$authplugin = get_auth_plugin('stripe');

require_login();

$coupon = optional_param('apply_coupon', false, PARAM_TEXT);

$url_params = [];
if (!empty($coupon)){
    $url_params['apply_coupon'] = $coupon;
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(MAIN_PAYMENT_URL, $url_params);

$page_type = \auth_stripe\core::MAIN_PAGE;
if (tier_processor::check_page($page_type) || \local_sql\moodle\role_manager::is_admin()){
    \auth_stripe\core::redirect_to_profile();
}

$PAGE->set_title(\auth_stripe\core::str('payment_title'));
$PAGE->set_heading($SITE->fullname);

stripe_render_payment_page($authplugin, $page_type, '', $coupon);