<?php

defined('MOODLE_INTERNAL') || die;

use local_referral\core;

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(core::PLUGIN_NAME, new lang_string('pluginname', core::PLUGIN_NAME)));
    $settings = new admin_settingpage('manage_local_referral', new lang_string('referral', core::PLUGIN_NAME));

    $settings->add(new admin_setting_configtextarea(
        core::PLUGIN_NAME . '/referral_coupons',
        core::str('config:referral_coupons'),
        core::str('config:referral_coupons_desc'),
        json_encode(
            [
                ["REFDISCOUNT10%" => ["id" => null, "is_coaching" => true]],
                ["REFDISCOUNT250" => ["id" => null, "is_coaching" => false]]
            ],
            JSON_PRETTY_PRINT
        ),
        PARAM_RAW
    ));

    $ADMIN->add('localplugins', $settings);
}