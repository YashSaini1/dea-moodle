<?php

/**
 * File display list of all registered coupons
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use auth_stripe\core;
use auth_stripe\loaders\coupon_promocode_loader;
use auth_stripe\model\promocode;
use auth_stripe\form\promocode_registration_form;
use auth_stripe\processor\promocode\promocode_registration;

require_once('../../../config.php');
redirect('/');
die();

require_once($CFG->dirroot.'/auth/stripe/locallib.php');

require_login();
if (!core::can_view_coupons()){
    redirect($CFG->wwwroot);
}

$hide_id = optional_param('hideid', false, PARAM_BOOL);

$ctx = context_system::instance();
$PAGE->set_context($ctx);
$PAGE->set_url(promocode::PAGE);

$title = core::str('promocode:manage');
$PAGE->set_heading($title);
$PAGE->set_title($title);

if (core::can_edit_coupon()){
    $form = new promocode_registration_form();

    if ($form_data = $form->get_data()){
        $promocode_registration = new promocode_registration();
        $error = $promocode_registration->register($form_data->promocodeid);
        if (!empty($error)){
            $form->setElementError('promocodeid', $error);
        } else {
            \core\notification::success(core::str('promocode:created'));
            redirect(promocode::PAGE);
        }
    }
}

stripe_body_add_admin_class();

$table = new html_table();
$table->attributes['class'] = 'generaltable promocode_table';
$head =  [
    '№',
];
if (!$hide_id && is_siteadmin()){
    $head[] = 'Promocode id';
}
$head = array_merge($head, [
    core::str('promocode:code'),
    core::str('promocode:stripeid'),
    core::str('coupon'),
    core::str('coupon:amount_off'),
    core::str('coupon:percent_off'),
    core::str('coupon:currency'),
    core::str('promocode:enabled'),
    '',
]);

$table->head = $head;

$cell = function($text, $attributes = []){
    $text = html_writer::div($text, 'cell_data');
    $cell = new html_table_cell($text);
    if(!empty($attributes)){
        if(empty($attributes['class'])){
            $attributes['class'] = '';
        }
        $cell->attributes = $attributes;
    }
    return $cell;
};

$link = function($url, $text, $attr = null) use ($cell){
    return $cell(html_writer::link($url, $text, $attr));
};


$enable_str = core::str('enable');
$disable_str = core::str('disable');
$updating_str = core::str('updating');
$PAGE->requires->js_call_amd('auth_stripe/coupon_page', 'init', [
    'strings' => [
        'updating' => $updating_str,
        'enable'   => $enable_str,
        'disable'  => $disable_str,
    ],
    'type' => 'promocode'
]);

$enabled_str = core::str('enabled');
$disabled_str = core::str('disabled');

$is_admin = !$hide_id && is_siteadmin();
// TODO: rewrite all of this via output class like user_tier_output
$i = 1;
$coupon_promocodes = coupon_promocode_loader::get_all();
foreach ($coupon_promocodes as $coupon_promocode){
    $promocode = $coupon_promocode->promocode;
    $coupon = $coupon_promocode->coupon;
    $row = [
        $cell($i, ['class' => $i]),
    ];

    if ($is_admin){
        $row[] = $cell($promocode->id);
    }
    $row[] = $cell($promocode->code);
    $row[] = $cell($promocode->stripeid);
    $row[] = $cell($coupon->name);

    $amount_off = !empty($coupon->amount_off) ? $coupon->amount_off : '-';
    $percent_off = !empty($coupon->percent_off) ? ($coupon->percent_off / 100).' %' : '-';

    $row[] = $cell($amount_off);
    $row[] = $cell($percent_off);

    $row[] = $cell($coupon->currency ?? '-');

    $enabled = $promocode->enabled ? $enabled_str : $disabled_str;
    $row[] = $cell($enabled);

    $button = '<button id="disable-'.$i.'" class="btn btn-secondary disable_btn"'.
        ' data-id="'.$promocode->id.'" data-newstate="'.intval(!$promocode->enabled).'">'
        .($promocode->enabled ? $disable_str : $enable_str).'</button>';
    $row[] = $cell($button);

    $table->data[] = new html_table_row($row);
    $i++;
}

echo $OUTPUT->header();
if (!empty($form)){
    $form->display();
}

echo html_writer::table($table);
echo $OUTPUT->footer();