<?php

/**
 * Local Ab Testing upgrade
 *
 * @package     local_ab_testing
 * @copyright   2024 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/ab_testing/db/upgradelib.php');

/**
 * Upgrade the local_ab_testing plugin.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 */
function xmldb_local_ab_testing_upgrade($oldversion){
    global $DB;
    $dbman = $DB->get_manager();
    $transaction = $DB->start_delegated_transaction();

    if ($oldversion < 2024021200) {
        local_ab_testing_upgrade_profile_fields_data([
            'price_600' => 'price_497',
            'price_900' => 'price_997',
        ]);


        $stripe = new \auth_stripe\stripe(null, false);
        $price = \auth_stripe\model\price::get([
            'ab_info' => 'price_600'
        ]);
        if ($price){
            $new_price = clone $price;
            $new_price->price = 49700;
            $new_price->ab_info = 'price_497';
            $stripe->update_price($price, $new_price);
        }

        $price = \auth_stripe\model\price::get([
            'ab_info' => 'price_900'
        ]);
        if ($price){
            $new_price = clone $price;
            $new_price->price = 99700;
            $new_price->ab_info = 'price_997';
            $stripe->update_price($price, $new_price);
        }
    }

    $transaction->allow_commit();
    return true;
}