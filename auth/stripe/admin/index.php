<?php

use auth_stripe\core;
use auth_stripe\form\admin_stripe;
use auth_stripe\model\product;

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');

require_login();
if (!is_siteadmin()){
    redirect($CFG->wwwwroot);
}

stripe_body_add_admin_class();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(PRODUCT_URL));

$heading = core::str('set_tier_product');
$PAGE->set_heading($heading);
$PAGE->set_title($heading);

$tiers = [];
foreach (range(1, DEFAULT_TIER_COUNT) as $tier){
    $tiers[$tier] = $tier;
}

$data = [];
$stripe_products = product::get_all_sorted_by_tier();
if ($stripe_products) {
    foreach ($stripe_products as $product) {
        $data['tier_'.$product->tier.'_name'] = $product->name;
        $data['tier_'.$product->tier.'_page'] = admin_stripe::get_page_index($product->sql_page);
    }
}

$mform = new admin_stripe(null, compact('tiers'));
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php?category=auth_stripe'));
} else if ($fromform = $mform->get_data()) {
    $stripe = new \auth_stripe\stripe();
    $products = product::get_all_sorted_by_tier();

    foreach ($tiers as $tier){
        $product = $products[$tier] ?? null;

        $newproduct = product::create_from_form($fromform, $tier);
        if (!$newproduct) {
            if(!empty($product)){
                $stripe->delete_product($product);
            }
            continue;
        }

        if (empty($newproduct->id)){
            $stripe->create_product($newproduct);
            continue;
        }

        $stripe->update_product($product,$newproduct);
    }

    redirect(new moodle_url('/admin/category.php?category=auth_stripe'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();