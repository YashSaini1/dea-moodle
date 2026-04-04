<?php

/**
 * Plugin strings are defined here.
 *
 * @package     local_sql
 * @category    string
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Local SQL plugin';
$string['created_course_task'] = 'Local SQL create course task';
$string['documentation'] = 'Documentation';
$string['cannot_view_module'] = 'You cannot see this activity!';
$string['terms'] = 'Terms and condition';
$string['privacy'] = 'Privacy Policy';
$string['documentation'] = 'Documentation';

/// Tasks
$string['task:course_created'] = 'SQL course created task';
$string['task:course_updated'] = 'SQL course updated task';
$string['task:paypal_status_update'] = 'SQL paypal status update task';

/// Configs
$string['config:manage'] = 'Manage Local Sql plugin settings';
$string['config:analytics_enabled'] = 'Enable analytics processing';
$string['config:analytics_enabled_desc'] = 'If disabled, custom analytics will not connected to the site';

$string['config:paypal_header'] = 'PayPal settings';
$string['config:paypal_header_desc'] = 'Config paypal settings';

$string['config:paypal_client_id'] = 'Client ID';
$string['config:paypal_client_id_desc'] = 'Client ID from PayPal dashboard';

$string['config:paypal_client_secret'] = 'Client Secret';
$string['config:paypal_client_secret_desc'] = 'Client Secret from PayPal dashboard';

$string['config:paypal_test_mode'] = 'Test mode';
$string['config:paypal_test_mode_desc'] = 'PayPal test mode [Not for production]';

$string['config:paypal_min_transaction'] = 'Minimum withdrawal amount in $';
$string['config:paypal_min_transaction_desc'] = 'PayPal Setting the minimum threshold for withdrawal of the referral balance';

$string['config:paypal_max_transaction'] = 'Maximum withdrawal amount in $';
$string['config:paypal_max_transaction_desc'] = 'PayPal Setting the maximum threshold for withdrawal of the referral balance';

$string['config:analytics_settings'] = 'Analytics configuration';
$string['config:analytics_settings_desc'] = '';