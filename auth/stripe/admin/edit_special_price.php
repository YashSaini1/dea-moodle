<?php

use auth_stripe\core;
use auth_stripe\form\admin_edit_special_price;
use auth_stripe\model\price;
use auth_stripe\model\price\price_description;
use auth_stripe\model\price\price_email;
use auth_stripe\model\product;
use auth_stripe\util\price_util;

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');

require_login();
if (!core::can_create_price()){
    redirect($CFG->wwwwroot);
}

$id = required_param('id', PARAM_INT);

$product = product::get_by_page(core::SPECIAL_PREMIUM_PAGE);
if (empty($product)){
    throw new moodle_exception('invalid product id');
}

stripe_body_add_admin_class();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(price::EDIT_SPECIAL_PRICE_URL));

$heading = core::str('special_price_editing');
//$PAGE->set_heading($heading);
$PAGE->set_title($heading);

if ($id == -1){
    $prices = price::get_product_prices($product->id);
    $prices[] = new price(['max_times' => '0', 'plan_name' => '', 'price' => '0']);
} else {
    $price = price::get(['id' => $id]);
    $prices = [$price] + $price->get_dependent_prices();
}

$data = price_util::prepare_price_formdata($prices);
$price_description = price_description::get_by_price($prices[0]);
$price_email = price_email::get_by_price($prices[0]);
$data['id'] = $id;
$data['productid'] = $product->id;
$data['description']['text'] = $price_description->info;
$data['email_text']['text'] = $price_email->email_text;
$data['is_coupon_allowed'][0] = $prices[0]->is_allow_coupon;
$data['is_checkout'][0] = $prices[0]->is_checkout;

$mform = new admin_edit_special_price(null, $data);
$mform->set_data($data);

if ($mform->is_cancelled()){
    redirect(new moodle_url(price::SPECIAL_PRICE_LIST_URL));
} elseif ($fromform = $mform->get_data()) {
    $stripe = new \auth_stripe\stripe();
    $previous = $previous_token = null;

    foreach ($fromform->price as $position => $price){
        $price = $prices[$position] ?? null;
        $price->price *= 100;

        $newprice = price::create_from_form($fromform, $position);
        if (!$newprice){
            continue;
        }
        if ($fromform->dependency[$position]){
            if (empty($previous)){
                $newprice->dependency = 0;
            } else {
                $newprice->dependency = $previous->id;
            }
        }

        if ($id == -1){
            $newprice->base_price = 0;
            $stripe->create_price($newprice);
            if (empty($newprice->dependency)){
                $previous_token = $newprice->generate_payment_token();
            } else {
                $newprice->save_token($previous_token);
            }
        } else {
            $newprice->base_price = $price->base_price;
            $newprice->id = $price->id;
            $newprice->priceid = $price->priceid;

            // Hard-code the new price information because we don't allow to update this values
            $newprice->price = $price->price;
            $newprice->period = $price->period;
            $newprice->max_times = $price->max_times;
            $stripe->update_price($price, $newprice);
        }

        if (empty($previous)){
            $email_text = html_entity_decode($fromform->email_text['text']); // decode <> symbols
            if ($id == -1){
                $price_description = new price_description([
                    'priceid' => $newprice->id,
                    'info'    => $fromform->description['text'],
                ]);
                $price_email = new price_email([
                    'priceid' => $newprice->id,
                    'email_text' => $email_text
                ]);
            } else {
                $price_description->info = $fromform->description['text'];
                $price_email->email_text = $email_text;
            }
            $price_description->save();
            $price_email->save();
        }

        $previous = $newprice;
    }

    redirect(new moodle_url(price::SPECIAL_PRICE_LIST_URL));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();