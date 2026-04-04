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
 * Local AB Testing plugin settings
 *
 * @package     local_ab_testing
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die;

use \local_ab_testing\core;

if($hassiteconfig){
    $ADMIN->add('localplugins', new admin_category(core::PLUGIN_NAME, new lang_string('pluginname', core::PLUGIN_NAME)));
    $settingspage = new admin_settingpage('manage_local_ab_testing', new lang_string('manage', core::PLUGIN_NAME));

    if($ADMIN->fulltree){
        $settingspage->add(new admin_setting_configcheckbox(core::PLUGIN_NAME.'/enable',
            core::str('config:enable'),
            core::str('config:enable_desc'),
            false
        ));

        $settingspage->add(new admin_setting_configtextarea(core::PLUGIN_NAME.'/ab_settings',
            core::str('config:ab_settings'),
            core::str('config:ab_settings_desc'),
            ''
        ));
    }
    $ADMIN->add('localplugins', $settingspage);
}