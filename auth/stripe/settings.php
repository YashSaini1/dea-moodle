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
 * Stripe plugin settings page
 *
 * @package     auth_stripe
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use auth_stripe\core;
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    require_once($CFG->dirroot . '/auth/stripe/lib.php');

    $settings->visiblename = core::str('auth_heading_stripe_description');

    $settings->add(new admin_setting_heading('auth_stripe','',
        new lang_string('auth_stripe_description', 'auth_stripe')));

    $settings->add(new admin_setting_heading(
        'auth_stripe/additional1',
        new lang_string('authentication_settings', 'auth_stripe'),
        new lang_string('authentication_settings_description', 'auth_stripe')));

    $settings->add(new admin_setting_heading(
        'auth_stripe/stripeendpoints',
        new lang_string('auth_heading_stripe_description', 'auth_stripe'),
        ''
    ));
    $options = array(
        new lang_string('no'),
        new lang_string('yes'),
    );
    $settings->add(new admin_setting_configselect('auth_stripe/recaptcha',
        new lang_string('auth_striperecaptcha_key', 'auth_stripe'),
        new lang_string('auth_striperecaptcha', 'auth_stripe'), 0, $options));

    $settings->add(new admin_setting_configtext('auth_stripe/secret_key', core::str('secret_stripekey'),
        '', '', PARAM_RAW));
    $settings->add(new admin_setting_configtext('auth_stripe/public_key', core::str('public_stripekey'),
        '', '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('auth_stripe/webhook_endpoint_key', core::str('webhook_endpoint'),
        core::str('webhook_endpoint_description'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('auth_stripe/currency', get_string('currency'),
        core::str('currency_description'), 'usd', PARAM_RAW));

    $settings->add(new admin_setting_configtext('auth_stripe/upgrade_stripe_link', core::str('upgrade_stripe_link'),
        core::str('upgrade_stripe_link_description'), '', PARAM_RAW));

    // Create category for Enrolkey.
    $ADMIN->add('authsettings', new admin_category('auth_stripe', core::str('pluginname')));
    // Add settings page toconfigure defaults.
    $ADMIN->add('auth_stripe', $settings);
    // Clear '$settings' to prevent adding again our site category.
    $settings = null;
    // Add options.
    $ADMIN->add('auth_stripe',
        new admin_externalpage(
            'auth_stripe_tiersprice',
            core::str('set_tier_product'),
            new moodle_url(PRODUCT_URL)
        )
    );
}
