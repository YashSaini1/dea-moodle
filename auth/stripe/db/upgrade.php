<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Authentication plugin upgrade code
 *
 * @package     auth_stripe
 * @category    upgrade
 * @copyright   2021 Kirill Slyusar
 */

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use auth_stripe\model\price;
use auth_stripe\model\price\price_description;
use auth_stripe\model\price\price_email;
use auth_stripe\model\product;
use auth_stripe\model\user_promo_banner;
use auth_stripe\stripe;

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade auth_email.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_stripe_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2022122300) {
        $table = new xmldb_table('auth_stripe_user_tier');
        $fields = [
            new xmldb_field('current_period_end', XMLDB_TYPE_INTEGER, '18', null, null, null, null),
            new xmldb_field('current_period_start', XMLDB_TYPE_INTEGER, '18', null, null, null, null)
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
    }

    if ($oldversion < 2023022700) {
        $table = new xmldb_table('auth_stripe_product');
        $field = new xmldb_field('sql_page', XMLDB_TYPE_CHAR, 40, null, XMLDB_NOTNULL, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('auth_stripe_product', 'sql_page', core::MAIN_PAGE);
    }

    if ($oldversion < 2023022800) {
        $table = new xmldb_table('auth_stripe_product');
        $field = new xmldb_field('plan_name', XMLDB_TYPE_CHAR, 100, null, XMLDB_NOTNULL, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('auth_stripe_product', 'plan_name', 'plan');
    }

    if ($oldversion < 2023030100) {
        $table = new xmldb_table('auth_stripe_price');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('plan_name', XMLDB_TYPE_CHAR, 100, null, XMLDB_NOTNULL, null, null);
        $table->add_field('productid', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, null);
        $table->add_field('price', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $table->add_field('priceid', XMLDB_TYPE_CHAR, 40, null, XMLDB_NOTNULL, null, null);
        $table->add_field('period', XMLDB_TYPE_CHAR, 40, null, XMLDB_NOTNULL, null, null);
        $table->add_field('currency', XMLDB_TYPE_CHAR, 5, null, XMLDB_NOTNULL, null, null);
        $table->add_field('dependency', XMLDB_TYPE_INTEGER, 11, null, null, null, 0);
        $table->add_field('max_times', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('as_price_product', XMLDB_INDEX_NOTUNIQUE, array('id', 'productid'));

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $dbman->create_table($table);

        $products = product::get_records();
        foreach ($products as $product){
            $DB->insert_record(stripe_database::TABLE_PRICE,[
                'productid' => $product->id,
                'price' => $product->price,
                'priceid' => $product->priceid,
                'period' => $product->period,
                'plan_name' => $product->plan_name,
                'currency' => $product->currency,
                'max_times' => -1,
            ]);
        }
        $table = new xmldb_table('auth_stripe_product');
        $fields = ['price', 'priceid', 'period', 'currency', 'plan_name'];
        foreach ($fields as $f){
            $field = new xmldb_field($f);

            // Conditionally launch drop field id.
            if ($dbman->field_exists($table, $field)){
                $dbman->drop_field($table, $field);
            }
        }
    }

    if ($oldversion < 2023030600) {
        $table = new xmldb_table('auth_stripe_user_tier');
        $field = new xmldb_field('priceid', XMLDB_TYPE_CHAR, 100, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2023030900){
        $table = new xmldb_table('auth_stripe_price_token');

        $table->add_field('id', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('priceid', XMLDB_TYPE_INTEGER, 10, null, false, null, 0);
        $table->add_field('token', XMLDB_TYPE_CHAR, 40, null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array("priceid"), 'auth_stripe_price', array("id"));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('auth_stripe_price');
        $field = new xmldb_field('base_price', XMLDB_TYPE_INTEGER, 1, null, false, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2023031300){
        $table = new xmldb_table('auth_stripe_user_tier_price');

        $table->add_field('id', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('priceid', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, null);
        $table->add_field('usertierid', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, null);
        $table->add_field('stripepriceid', XMLDB_TYPE_CHAR, 100, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('usertier_price', XMLDB_KEY_FOREIGN, array('priceid'), 'auth_stripe_price', array('id'));
        $table->add_key('usertier_tier', XMLDB_KEY_FOREIGN, array('usertierid'), 'auth_stripe_user_tier', array('id'));

        if (!$dbman->table_exists($table)){
            $dbman->create_table($table);
        }

        $table = new xmldb_table('auth_stripe_user_tier');
        $field = new xmldb_field('priceid');
        if ($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }
    }

    if ($oldversion < 2023031400){
        $sql = "SELECT u.id
                FROM {user} u
                LEFT JOIN {auth_stripe_user_tier} ut ON ut.userid = u.id AND ut.tier = 0
                WHERE ut.id is NULL and u.deleted = 0";
        $users = $DB->get_records_sql($sql);
        foreach ($users as $user){
            \auth_stripe\subscription\tier_processor::empty_tier($user->id);
        }
    }
    if ($oldversion < 2023032000){
        $DB->set_field_select(stripe_database::TABLE_PRICE, 'base_price', 1, 'id > 0');
    }

    if ($oldversion < 2023060601){
        $table = new xmldb_table('auth_stripe_price');
        $field = new xmldb_field('enabled',XMLDB_TYPE_INTEGER, 1, null, null, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('ab_info', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2023070400){
        $table = new xmldb_table('auth_stripe_price');
        $field = new xmldb_field('owner',XMLDB_TYPE_INTEGER, 11, 1, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('price_owner', XMLDB_KEY_FOREIGN, 'owner', 'user', 'id');
        if (!$table->getKey('price_owner')){
            $table->addKey($key);
        }
    }

    if ($oldversion < 2023072101){
        $table = new xmldb_table(stripe_database::TABLE_USER_TIER);
        $fields = [
            new xmldb_field('can_cancel', XMLDB_TYPE_INTEGER, 1, 1, null, null, 0),
            new xmldb_field('time_cancelled', XMLDB_TYPE_INTEGER, 10, 1, null, null),
        ];

        foreach ($fields as $field){
            if (!$dbman->field_exists($table, $field)){
                $dbman->add_field($table, $field);
            }
        }

        $product = product::get_by_page(core::MAIN_PAGE);
        if ($product){
            $sql = "UPDATE {auth_stripe_user_tier_price} utp
                    JOIN {auth_stripe_user_tier} ut ON utp.usertierid = ut.id
                    SET ut.can_cancel = 1
                    WHERE ut.tier = ?";

            $DB->execute($sql, [$product->tier]);
        }
    }

    if ($oldversion < 2023092601){
        $table = new xmldb_table(stripe_database::TABLE_PRICE_DESCRIPTION);

        $table->add_field('id', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('priceid', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, null);
        $table->add_field('info', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)){
            $dbman->create_table($table);
        }

        // Init product
        $stripe = new stripe();
        $tier = $DB->get_field_sql('SELECT MAX(tier) from {'.product::table().'}');
        $product = new product([
            'tier'     => $tier + 1,
            'sql_page' => core::SPECIAL_PREMIUM_PAGE,
            'name'     => 'Special Coaching',
        ]);
        $stripe->create_product($product);

        $price = new price([
            'plan_name' => 'One Time $497 Coaching',
            'price'     => 4970000, //set up more zeros because we need a 497.00 , but in price class there is a division by 100
            'productid' => $product->id,
            'period'    => core::PERIOD_ONE_TIME,
            'max_times' => -1,
        ]);
        $stripe = new stripe();
        $stripe->create_price($price);

        $price_description = new price_description([
            'priceid' => $price->id,
            'info'    => '<p>Data engineer interview guide<br>Annual portal access<br>2 one hour coaching sessions<br>Resume review<br>Personalized plan</p>',
        ]);
        $price_description->save();

        $data = get_config('core', 'custommenuitems');
        set_config('custommenuitems', $data.PHP_EOL.'Manage Special Prices|/auth/stripe/admin/special_list.php');
    }

    if ($oldversion < 2023110204){
        $table = new xmldb_table('auth_stripe_product_course');
        if ($dbman->table_exists($table)){
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('auth_stripe_coupon');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, 60, null, XMLDB_NOTNULL, null);
        $table->add_field('stripeid', XMLDB_TYPE_CHAR, 40, null, XMLDB_NOTNULL, null, null);
        $table->add_field('amount_off', XMLDB_TYPE_INTEGER, 10, null, null, null, null);
        $table->add_field('percent_off', XMLDB_TYPE_INTEGER, 10, null, null, null, null);
        $table->add_field('currency', XMLDB_TYPE_CHAR, 5, null, null, null, null);
        $table->add_field('duration', XMLDB_TYPE_CHAR, '15', null, null, null, null);
        $table->add_field('duration_in_months', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('owner', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, 1, null, null, null, 1);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('coupon_owner', XMLDB_KEY_FOREIGN, ['owner'], 'user', ['id']);
        $table->add_index('coupon_name', XMLDB_INDEX_NOTUNIQUE, array('name'));

        if (!$dbman->table_exists($table)){
            $dbman->create_table($table);
        }

        // Define table auth_stripe_promocode to be created.
        $table = new xmldb_table('auth_stripe_promocode');

        // Adding fields to table auth_stripe_promocode.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null);
        $table->add_field('couponid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('stripeid', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('owner', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, null, null, '1');

        // Adding keys to table auth_stripe_promocode.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('promocode_owner', XMLDB_KEY_FOREIGN, ['owner'], 'user', ['id']);
        $table->add_key('promocode_coupon', XMLDB_KEY_FOREIGN, ['couponid'], 'auth_stripe_coupon', ['id']);

        // Adding indexes to table auth_stripe_promocode.
        $table->add_index('promocode_code', XMLDB_INDEX_NOTUNIQUE, ['code']);

        // Conditionally launch create table for auth_stripe_promocode.
        if (!$dbman->table_exists($table)){
            $dbman->create_table($table);
        }

        set_config('custommenuitems','Manage Data
-Datasets|/question/type/sqlrunner/dataset/list.php
-###
-Comments|/comment/index.php

Payment
-Prices|/auth/stripe/admin/list.php
-Special Prices|/auth/stripe/admin/special_list.php
-###
-Coupons|/auth/stripe/admin/coupons.php');
    }

    if ($oldversion < 2023112801){
        // Define table auth_stripe_user_promobanner to be created.
        $table = new xmldb_table('auth_stripe_user_promobanner');

        // Adding fields to table auth_stripe_user_promobanner.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, null, null, '');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timedue', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table auth_stripe_user_promobanner.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for auth_stripe_user_promobanner.
        if (!$dbman->table_exists($table)){
            $dbman->create_table($table);
        }
        $data = get_config('core', 'custommenuitems');
        set_config('custommenuitems', $data.PHP_EOL.'-###'.PHP_EOL.'-Promobanner|'.user_promo_banner::PAGE);

        $promo = \auth_stripe\model\dto\promobanner_config::get_default();
        $promo->save();
    }

    if ($oldversion < 2024010302){
        mkdir($CFG->dirroot.core::PDF_FOLDER, 0644);
        file_put_contents($CFG->dirroot.core::PDF_FOLDER.'/.htaccess', 'Options -Indexes
Redirect 404');

        // Define table auth_stripe_send_invoices to be created.
        $table = new xmldb_table('auth_stripe_send_invoices');

        // Adding fields to table auth_stripe_send_invoices.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('invoiceid', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('priceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table auth_stripe_send_invoices.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('price', XMLDB_KEY_FOREIGN, ['priceid'], 'auth_stripe_price', ['id']);
        $table->add_key('product', XMLDB_KEY_FOREIGN, ['productid'], 'auth_stripe_product', ['id']);

        // Adding indexes to table auth_stripe_send_invoices.
        $table->add_index('as_price_product', XMLDB_INDEX_NOTUNIQUE, ['invoiceid']);

        // Conditionally launch create table for auth_stripe_send_invoices.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2024010501){
        // Define field couponid to be added to auth_stripe_user_tier_price.
        $table = new xmldb_table('auth_stripe_user_tier_price');
        $field = new xmldb_field('couponid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'stripepriceid');

        // Conditionally launch add field couponid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('usertier_coupon', XMLDB_KEY_FOREIGN, ['couponid'], 'auth_stripe_coupon', ['id']);
        // Launch add key usertier_coupon.
        $dbman->add_key($table, $key);

        // Define field time_created to be added to auth_stripe_user_tier.
        $table = new xmldb_table('auth_stripe_user_tier');
        $field = new xmldb_field('time_created', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'time_cancelled');

        // Conditionally launch add field time_created.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2024013001) {
        // Define table auth_stripe_price_email to be created.
        $table = new xmldb_table('auth_stripe_price_email');

        // Adding fields to table auth_stripe_price_email.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('priceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table auth_stripe_price_email.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('price_email', XMLDB_KEY_FOREIGN, ['priceid'], 'auth_stripe_price', ['id']);

        // Conditionally launch create table for auth_stripe_price_email.
        if (!$dbman->table_exists($table)){
            $dbman->create_table($table);
        }

        $product = product::get_by_page(core::SPECIAL_PREMIUM_PAGE);
        $data = $DB->get_records_sql("SELECT p.*
            FROM {".price::table()."} p
            LEFT JOIN {".price_email::table()."} pe ON pe.priceid = p.id
            WHERE p.productid = ? AND p.dependency IS NULL AND pe.id IS NULL", [$product->id]);

        $text = core::str('newusernewspecialoffer', []);
        foreach ($data as $price_data){
            price_email::create([
                'priceid'    => $price_data->id,
                'email_text' => $text,
            ]);
        }
    }


    if ($oldversion < 2024112703) {
        $checkout_table = new xmldb_table('auth_stripe_checkout');

        $checkout_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $checkout_table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $checkout_table->add_field('checkoutid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $checkout_table->add_field('priceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $checkout_table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, time());

        $checkout_table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $checkout_table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        if (!$dbman->table_exists($checkout_table)) {
            $dbman->create_table($checkout_table);
        }
    }

    return true;
}