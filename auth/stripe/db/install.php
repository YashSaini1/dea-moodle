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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     auth_stripe
 * @category    install
 * @copyright   2021 Kirill Slyusar
 */

use auth_stripe\core;

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_auth_stripe_install() {
    global $DB, $CFG;

    $data = get_config('core', 'custommenuitems');
    set_config('custommenuitems', $data.PHP_EOL.'Manage Data
-Datasets|/question/type/sqlrunner/dataset/list.php
-###
-Comments|/comment/index.php

Payment
-Prices|/auth/stripe/admin/list.php
-Special Prices|/auth/stripe/admin/special_list.php
-###
-Coupons|/auth/stripe/admin/coupons.php');

    mkdir($CFG->dirroot .'/auth/stripe/logs');

    mkdir($CFG->dirroot.core::PDF_FOLDER, 0644);
    file_put_contents($CFG->dirroot.core::PDF_FOLDER.'/.htaccess', 'Options -Indexes
Redirect 404');
}