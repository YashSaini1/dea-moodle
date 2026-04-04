<?php

defined('MOODLE_INTERNAL') || die;

use local_crm\core;

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(core::PLUGIN_NAME, new lang_string('pluginname', core::PLUGIN_NAME)));
    $settings = new admin_settingpage('manage_local_crm', new lang_string('crm', core::PLUGIN_NAME));

    $settings->add(new admin_setting_configtext(
        core::PLUGIN_NAME . '/ontraport_app_id',
        core::str('config:ontraport_app_id'),
        core::str('config:ontraport_app_id_desc'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        core::PLUGIN_NAME . '/ontraport_api_key',
        core::str('config:ontraport_api_key'),
        core::str('config:ontraport_api_key_desc'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        core::PLUGIN_NAME . '/ontraport_is_send',
        core::str('config:ontraport_is_send'),
        core::str('config:ontraport_is_send_desc'),
        false
    ));

    $settings->add(new admin_setting_configtext(
        core::PLUGIN_NAME . '/closecrm_api_key',
        core::str('config:closecrm_api_key'),
        core::str('config:closecrm_api_key_desc'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        core::PLUGIN_NAME . '/closecrm_is_send',
        core::str('config:closecrm_is_send'),
        core::str('config:closecrm_is_send_desc'),
        false
    ));

    $ADMIN->add('localplugins', $settings);
}