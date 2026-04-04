<?php

/**
 * File display list of all registered coupons
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use auth_stripe\core;
use auth_stripe\form\coupon_registration_form;
use auth_stripe\model\coupon;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\processor\coupon\coupon_registration;
use auth_stripe\util\price_display_util;

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');

require_login();
if (!core::can_view_coupons()){
    redirect($CFG->wwwroot);
}

$hide_id = optional_param('hideid', false, PARAM_BOOL);

$ctx = context_system::instance();
$PAGE->set_context($ctx);
$PAGE->set_url(coupon::PAGE);

$title = core::str('coupon:manage');
$PAGE->set_heading($title);
$PAGE->set_title($title);

if (core::can_edit_coupon()){
    $form = new coupon_registration_form();

    if ($form_data = $form->get_data()){
        $coupon_registration = new coupon_registration();
        $error = $coupon_registration->register($form_data->couponid);
        if (!empty($error)){
            $form->setElementError('couponid', $error);
        } else {
            \core\notification::success(core::str('coupon:created'));
            redirect(coupon::PAGE);
        }
    }
}

stripe_body_add_admin_class();

$table = new html_table();
$table->attributes['class'] = 'generaltable coupon_table';
$head = [
    '№',
];
if (!$hide_id && is_siteadmin()){
    $head[] = 'Coupon id';
}
$head = array_merge($head, [
    core::str('coupon:name'),
    core::str('coupon:stripeid'),
    core::str('coupon:amount_off'),
    core::str('coupon:currency'),
    core::str('coupon:percent_off'),
    core::str('coupon:duration'),
    core::str('coupon:duration_in_months'),
    core::str('coupon:enabled'),
    '',
]);

$table->head = $head;

$cell = function($text, $attributes = [], $classes = ''){
    $text = html_writer::div($text, 'cell_data '.$classes);
    $cell = new html_table_cell($text);
    if (!empty($attributes)){
        if (empty($attributes['class'])){
            $attributes['class'] = '';
        }
        $cell->attributes = $attributes;
    }
    return $cell;
};

$link = function($url, $text, $attr = null) use ($cell){
    return $cell(html_writer::link($url, $text, $attr));
};

$coupons = coupon::get_all();

$enable_str = core::str('enable');
$disable_str = core::str('disable');
$updating_str = core::str('updating');
$PAGE->requires->js_call_amd('auth_stripe/coupon_page', 'init', [
    'strings' => [
        'updating' => $updating_str,
        'enable'   => $enable_str,
        'disable'  => $disable_str,
    ],
    'type'    => 'coupon',
]);

$enabled_str = core::str('enabled');
$disabled_str = core::str('disabled');

$promobanner_config = promobanner_config::get_instance();
$promobanner_coupon = $promobanner_config->enabled ? $promobanner_config->couponname : false;

$is_admin = !$hide_id && is_siteadmin();
// TODO: rewrite all of this via output class like user_tier_output
$i = 1;
foreach ($coupons as $coupon){
    $row = [
        $cell($i, ['class' => $i]),
    ];

    if ($is_admin){
        $row[] = $cell($coupon->id);
    }
    $row[] = $cell($coupon->name, null, 'coupon_name');
    $row[] = $cell($coupon->stripeid);

    $amount_off = !empty($coupon->amount_off) ? price_display_util::format_price($coupon->amount_off / 100) : '-';
    $percent_off = !empty($coupon->percent_off) ? ($coupon->percent_off / 100).' %' : '-';

    $row[] = $cell($amount_off);
    $row[] = $cell($coupon->currency ?? '-');
    $row[] = $cell($percent_off);

    $row[] = $cell($coupon->duration);
    $row[] = $cell($coupon->duration_in_months > 0 ? $coupon->duration_in_months : '-');

    $enabled = $enabled_str;
    $classes = '';
    if (!$coupon->enabled){
        $enabled = $disabled_str;
        $classes = 'text-disabled';
    }
    $row[] = $cell($enabled, [], $classes);

    $button = '<button id="disable-'.$i.'" class="btn btn-secondary disable_btn"'.
        ' data-id="'.$coupon->id.'" data-newstate="'.intval(!$coupon->enabled).'">'
        .($coupon->enabled ? $disable_str : $enable_str).'</button>';
    $row[] = $cell($button);

    $row = new html_table_row($row);
    if ($coupon->name == $promobanner_coupon){
        $row->attributes['class'] .= ' used_in_promobanner';
        $button_cell = end($row->cells);
        $button_cell->text = '';
    }
    $table->data[] = $row;
    $i++;
}

echo $OUTPUT->header();
if (!empty($form)){
    $form->display();
}

echo html_writer::table($table);
echo $OUTPUT->footer();