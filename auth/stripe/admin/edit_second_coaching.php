<?php

use auth_stripe\core;
use auth_stripe\form\admin_edit_second_coaching;
use auth_stripe\model\price;
use auth_stripe\model\price\price_description;
use auth_stripe\model\product;
use auth_stripe\util\price_util;

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');

require_login();
if (!core::can_create_price()){
    redirect($CFG->wwwroot);
}

$id = required_param('id', PARAM_INT);

$product = product::get_by_page(core::SECOND_COACHING_PAGE);
if (empty($product)){
    throw new moodle_exception('invalid product id');
}

stripe_body_add_admin_class();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/auth/stripe/admin/edit_second_coaching.php', ['id' => $id]));

$heading = core::str('set_product_prices');
$PAGE->set_title($heading);

// Load the price and its dependents
if ($id == -1){
    $prices = price::get_product_prices($product->id);
} else {
    $price = price::get(['id' => $id]);
    if (!$price) {
        throw new moodle_exception('Price not found');
    }
    $prices = [$price] + $price->get_dependent_prices();
}

$data = price_util::prepare_price_formdata($prices);

$data['id'] = $id;
$data['productid'] = $product->id;

$price_description = price_description::get_by_price($prices[0]);
$data['description']['text'] = $price_description ? $price_description->info : '';

// Prepare boolean fields for checkboxes
foreach ($prices as $i => $p) {
    $data['is_coupon_allowed'][$i] = $p->is_allow_coupon;
    $data['is_checkout'][$i] = $p->is_checkout;
}

if ($id == -1) {
    for ($i = 0; $i < count($prices); $i++) {
        if (!isset($data['is_coupon_allowed'][$i])) {
            $data['is_coupon_allowed'][$i] = 1;
        }
        if (!isset($data['is_checkout'][$i])) {
            $data['is_checkout'][$i] = 1;
        }
    }
}

$mform = new admin_edit_second_coaching(null, $data);
$mform->set_data($data);

if ($mform->is_cancelled()){
    redirect(new moodle_url(price::PRICE_LIST_URL));
} elseif ($fromform = $mform->get_data()) {
    $stripe = new \auth_stripe\stripe();
    $previous = $previous_token = null;

    foreach ($fromform->price as $position => $val){
        // Get the existing price object to preserve immutable fields or for update comparison
        $old_price_obj = $prices[$position] ?? null;

        // Create new price object from form submission
        $newprice = price::create_from_form($fromform, $position);
        if (!$newprice){
            continue;
        }

        // Handle dependency logic
        if ($fromform->dependency[$position]){
            if (empty($previous)){
                $newprice->dependency = 0;
            } else {
                $newprice->dependency = $previous->id;
            }
        }

        if ($id == -1){
            // Creation mode logic
            $newprice->base_price = 0;
            $stripe->create_price($newprice);
            if (empty($newprice->dependency)){
                $previous_token = $newprice->generate_payment_token();
            } else {
                $newprice->save_token($previous_token);
            }
        } else {
            // Update mode
            if ($old_price_obj) {
                $newprice->base_price = $old_price_obj->base_price;
                $newprice->id = $old_price_obj->id;
                $newprice->priceid = $old_price_obj->priceid;
                $old_price_obj->price *= 100;
                $stripe->update_price($old_price_obj, $newprice);
            }
        }

        if (empty($previous)){
            if ($id == -1){
                $price_description = new price_description([
                    'priceid' => $newprice->id,
                    'info'    => $fromform->description['text'],
                ]);
            } else {
                if (!$price_description) {
                    $price_description = new price_description([
                        'priceid' => $newprice->id,
                        'info'    => $fromform->description['text'],
                    ]);
                } else {
                    $price_description->info = $fromform->description['text'];
                }
            }
            $price_description->save();
        }

        $previous = $newprice;
    }

    redirect(new moodle_url(price::PRICE_LIST_URL));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();