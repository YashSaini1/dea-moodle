<?php
// This file is part of eMailTest plugin for Moodle - http://moodle.org/
//
// eMailTest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// eMailTest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with eMailTest.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local SQL plugin settings
 *
 * @package     local_sql
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die;

use \local_sql\core;

if($hassiteconfig){
    $ADMIN->add('localplugins', new admin_category(core::PLUGIN_NAME, new lang_string('pluginname', core::PLUGIN_NAME)));
    $settingspage = new admin_settingpage('manage_local_sql', new lang_string('config:manage', core::PLUGIN_NAME));

    if($ADMIN->fulltree){
        $settingspage->add(new admin_setting_configcheckbox(core::PLUGIN_NAME.'/analytics_enabled',
            core::str('config:analytics_enabled'),
            core::str('config:analytics_enabled_desc'),
            false
        ));

        $settingspage->add(new admin_setting_configtextarea(core::PLUGIN_NAME.'/analytics_settings',
            core::str('config:analytics_settings'),
            core::str('config:analytics_settings_desc'),
            ''
        ));


        $settingspage->add(new admin_setting_configtext(
            core::PLUGIN_NAME . '/paypal_client_id',
            core::str('config:paypal_client_id'),
            core::str('config:paypal_client_id_desc'),
            ''
        ));

        $settingspage->add(new admin_setting_configtext(
            core::PLUGIN_NAME . '/paypal_client_secret',
            core::str('config:paypal_client_secret'),
            core::str('config:paypal_client_secret_desc'),
            ''
        ));

        $settingspage->add(new admin_setting_configcheckbox(
            core::PLUGIN_NAME . '/paypal_test_mode',
            core::str('config:paypal_test_mode'),
            core::str('config:paypal_test_mode_desc'),
            false
        ));

        $settingspage->add(new admin_setting_configtext(
            core::PLUGIN_NAME . '/paypal_min_transaction',
            core::str('config:paypal_min_transaction'),
            core::str('config:paypal_min_transaction_desc'),
            '100',
            PARAM_INT
        ));

        $settingspage->add(new admin_setting_configtext(
            core::PLUGIN_NAME . '/paypal_max_transaction',
            core::str('config:paypal_max_transaction'),
            core::str('config:paypal_max_transaction_desc'),
            '100000',
            PARAM_INT
        ));
    }
    $ADMIN->add('localplugins', $settingspage);
}