<?php

/**
 * Promobanner settings page
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use auth_stripe\core;
use auth_stripe\form\coupon_registration_form;
use auth_stripe\form\promobanner;
use auth_stripe\model\coupon;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\user_promo_banner;

require_once('../../../config.php');

require_login();
if (!core::can_edit_coupon()){
    redirect($CFG->wwwroot);
}

$ctx = context_system::instance();
$PAGE->set_context($ctx);
$PAGE->set_url(user_promo_banner::PAGE);

$title = core::str('promobanner:settings');
$PAGE->set_heading($title);
$PAGE->set_title($title);

stripe_body_add_admin_class();

$promobanner_config = promobanner_config::get_instance();
$data = [
    'enabled'           => $promobanner_config->enabled,
    'blackfriday'       => $promobanner_config->blackfriday,
    'couponname'        => $promobanner_config->couponname,
    'banner_text'       => $promobanner_config->text,
    'banner_text_short' => $promobanner_config->text_short,
    'duration'          => $promobanner_config->duration,
    'duration_period'   => array_search($promobanner_config->duration_period, user_promo_banner::PERIODS),
];
$form = new promobanner(null, ['config' => $promobanner_config]);
$form->set_data($data);
if ($form->is_cancelled()){
    // Nothing to do
} elseif ($formdata = $form->get_data()) {
    $promobanner_config->enabled = $formdata->enabled;
    $promobanner_config->blackfriday = $formdata->blackfriday;
    $promobanner_config->couponname = $formdata->couponname;
    $promobanner_config->text = $formdata->banner_text;
    $promobanner_config->text_short = $formdata->banner_text_short;
    $promobanner_config->duration = $formdata->duration;
    $promobanner_config->duration_period = user_promo_banner::PERIODS[$formdata->duration_period];
    $promobanner_config->save();
    \core\notification::success(get_string('changessaved'));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();