<?php

use auth_stripe\core;
use auth_stripe\form\admin_add_second_coaching;
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

$product = product::get_by_page(core::SECOND_COACHING_PAGE);
if (empty($product)){
    throw new moodle_exception('invalid product id');
}

stripe_body_add_admin_class();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(price::ADD_PRICE_URL));

$heading = core::str('set_product_prices');
$PAGE->set_title($heading);

$prices = price::get_product_prices($product->id);
$data = price_util::prepare_price_formdata($prices);

$data['productid'] = $product->id;
$data['id'] = -1;
$data['description']['text'] = '';

$mform = new admin_add_second_coaching(null, $data);
$mform->set_data($data);

if ($mform->is_cancelled()){
    redirect(new moodle_url(price::PRICE_LIST_URL));
} elseif ($fromform = $mform->get_data()) {
    $stripe = new \auth_stripe\stripe();
    $previous = $previous_token = null;

    foreach ($fromform->price as $position => $price_val){
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

        $stripe->create_price($newprice);
        if (empty($newprice->dependency)){
            $previous_token = $newprice->generate_payment_token();

            $price_description = new price_description([
                'priceid' => $newprice->id,
                'info'    => $fromform->description['text'],
            ]);
            $price_description->save();
        } else {
            $newprice->save_token($previous_token);
        }

        $previous = $newprice;
    }

    redirect(new moodle_url(price::PRICE_LIST_URL));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();