<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code that is executed before the tables and data are dropped during the plugin uninstallation.
 *
 * @package     auth_stripe
 * @category    uninstall
 * @copyright   2021 Kirill Slyusar
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstallation procedure.
 */
/*function xmldb_auth_stripe_uninstall() {

    global $DB;

    $dbman = $DB->get_manager();
    $tables = array(
        'auth_stripe_product',
        'auth_stripe_customer',
        'auth_stripe_user_tier',
        'auth_stripe_product_course',
        'auth_stripe_payment'
    );
    foreach ($tables as $table_name){
        $table = new xmldb_table($table_name);
        if ($dbman->table_exists($table_name)){
            $dbman->drop_table($table);
        }
    }

    return true;
}*/
