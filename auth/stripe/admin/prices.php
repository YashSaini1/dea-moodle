<?php

use auth_stripe\core;
use auth_stripe\form\admin_price;
use auth_stripe\model\price;
use auth_stripe\model\product;

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');

require_login();
if (!is_siteadmin()){
    redirect($CFG->wwwwroot);
}

$productid = required_param('id', PARAM_INT);

$product = product::get_by_id($productid);
if (empty($product)){
    throw new moodle_exception('invalid product id');
}

stripe_body_add_admin_class();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(PRICE_URL, ['id' => $productid]));

$heading = core::str('set_product_prices');
$PAGE->set_heading($heading);
$PAGE->set_title($heading);

$data = [];
$prices = price::get_product_prices($productid);
if (!empty($prices)) {
    $data = [
        'period' => [],
        'price' => [],
        'max_times' => [],
        'plan_name' => [],
        'dependency' => [],
        'enabled' => [],
        'ab_info' => [],
    ];
    foreach ($prices as $price) {
        $data['period'][] = admin_price::get_period_index($price->period);
        $data['price'][] = $price->price;
        $data['max_times'][] = $price->max_times;
        $data['plan_name'][] = $price->plan_name;
        $data['dependency'][] = $price->dependency;
        $data['enabled'][] = intval($price->enabled);
        $data['ab_info'][] = $price->ab_info;
    }
}

$data['productid'] = $productid;
$mform = new admin_price(null, $data);
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php?category=auth_stripe'));
} else if ($fromform = $mform->get_data()) {
    $stripe = new \auth_stripe\stripe();
    $previous = null;
    foreach ($fromform->price as $position => $price){
        $price = $prices[$position] ?? null;

        $newprice = price::create_or_update_from_form($fromform, $position);
        if (!$newprice){
            if (!empty($price)){
                $stripe->delete_price($price);
            }
            continue;
        }

        if ($fromform->dependency[$position]){
            if (empty($previous)){
                $newprice->dependency = 0;
            } else {
                $newprice->dependency = $previous->id;
            }
        }

        $previous = $newprice;
        $newprice->base_price = 1;
        if (empty($newprice->id)){
            $stripe->create_price($newprice);
            continue;
        }

        $price->price *= 100;
        $price->base_price = 1;
        $stripe->update_price($price, $newprice);
    }

    redirect(new moodle_url('/admin/category.php?category=auth_stripe'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();