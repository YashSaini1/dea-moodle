<?php

/**
 * Payment coaching course page.
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_stripe\subscription\tier_processor;

require('../../../config.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');

/**
 * @var auth_plugin_stripe $authplugin
 */
$authplugin = signup_is_enabled();
if (!$authplugin || $authplugin->authtype != 'stripe'){
    throw new \moodle_exception('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
}

$page_type = \auth_stripe\core::COACHING_PAGE;
if (tier_processor::check_page($page_type) || \local_sql\coaching::has_coaching()){
    \auth_stripe\core::redirect_to_profile();
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(COACHING_PAYMENT_URL);
$PAGE->set_title(\auth_stripe\core::str('payment_title'));
$PAGE->set_heading($SITE->fullname);

stripe_render_payment_page($authplugin, $page_type);