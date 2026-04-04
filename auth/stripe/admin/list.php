<?php

/**
 * File display list of all prises
 *
 * @package    auth
 * @subpackage stripe
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use auth_stripe\core;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\util\price_display_util;

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');

require_login();
if (!core::can_view_created_prices()){
    redirect($CFG->wwwroot);
}

$hide_id = optional_param('hideid', false, PARAM_BOOL);

stripe_body_add_admin_class();

$ctx = context_system::instance();
$PAGE->set_context($ctx);
$PAGE->set_url(price::PRICE_LIST_URL);

$title = core::str('price:manage_prices');
$PAGE->set_heading($title);
$PAGE->set_title($title);

if (core::can_create_price()){
    $add_btn = html_writer::link(new moodle_url(price::ADD_PRICE_URL), core::str('price:add_price'), ['class' => 'btn btn-primary']);
    $PAGE->set_button($add_btn);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable prices_table';
$head = [
    '№',
];
if (!$hide_id && is_siteadmin()){
    $head[] = 'Price id';
}
$head = array_merge($head, [
    core::str('price:plan_name'),
    core::str('price:price'),
    core::str('price:period'),
    core::str('price:max_times'),
    core::str('price:token_url'),
    '',
    '',
]);
$table->head = $head;

$cell = function($text, $attributes = []){
    $text = html_writer::div($text, 'cell_data');
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
$product = product::get_by_page(core::SECOND_COACHING_PAGE);

/**
 * @var price[] $prices
 * @var price[] $dependent_prices
 */
[$prices, $tokens] = get_prices_with_tokens($product->id);
$dependent_prices = price::get_all_dependent_prices(['productid' => $product->id]);

$copy_str = core::str('copy');
$copied_str = core::str('copied');
$PAGE->requires->js_call_amd('auth_stripe/list_page', 'init', [
    'strings' => [
        'copy'   => $copy_str,
        'copied' => $copied_str,
    ],
]);

$periods = [];
foreach (price::PERIODS as $period){
    $periods[$period] = core::str('period:'.$period);
}

$is_admin = !$hide_id && is_siteadmin();
// TODO: rewrite all of this via output class like user_tier_output
$i = 1;
foreach ($prices as $price){
    if ($price->dependency != 0){
        continue;
    }

    $has_dependency = !empty($dependent_prices[$price->id]);
    $row = [
        $cell($i, ['class' => $i]),
    ];

    if ($is_admin){
        $row[] = $cell($price->id);
    }
    $row[] = $cell($price->plan_name);

    if (core::is_period_price($price->period)){
        $max_times = $price->max_times;
    } else {
        $max_times = '-';
    }

    $price_info = '$'.price_display_util::format_price($price->price);
    $period = $periods[$price->period];
    if ($has_dependency){
        $price_info .= '<br>$'.price_display_util::format_price($dependent_prices[$price->id]->price);
        $period .= '<br>'.$periods[$dependent_prices[$price->id]->period];

        if (core::is_period_price($dependent_prices[$price->id]->period)){
            $max_times .= '<br>'.$dependent_prices[$price->id]->max_times;
        }
    }

    $row[] = $cell($price_info);
    $row[] = $cell($period);
    $row[] = $cell($max_times);
    $url = get_url_from_token($tokens[$price->id] ?? null)->out(false);
    $row[] = $cell($url, ['class' => 'url_wrapper']);
    $button = '<button id="copy-'.$i.'" class="btn btn-secondary copy_btn" data-copy="'.$url.'">Copy</button>';
    $row[] = $cell($button);

    // Edit Button
    $edit_url = new moodle_url('/auth/stripe/admin/edit_second_coaching.php', ['id' => $price->id]);
    $row[] = $link($edit_url, core::str('price:edit_price'), ['class' => 'btn btn-secondary']);

    $table->data[] = new html_table_row($row);
    $i++;
}

echo $OUTPUT->header();
echo html_writer::table($table);
echo $OUTPUT->footer();