<?php

/**
 * Plugin strings are defined here.
 *
 * @package     local_ab_testing
 * @category    string
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Local SQL A/B Testing';
$string['manage'] = 'Manage SQL A/B Testing';

$string['config:enable'] = 'Enable AB Testing';
$string['config:enable_desc'] = 'Enable situation when displayed content will changed';

$string['config:ab_settings'] = 'A/B testing configuration';
$string['config:ab_settings_desc'] = 'This is the JSON object which contains all necessary config data for tests. 

General rules:'.PHP_EOL.'
- the global object contains fields as test classname (without "_test" postfix)
- config must have a field "enabled" - bool (true|false)
- config must have a field "metrics" - object, in which fields - local utm names and values - real names
- config must have a field "campaign_parameter" - string with local utm metric name (e.g. "utm1")
- config must have a field "available_campaigns" - array with google available campaign names for this test 
- config must have a field "default" - string, default campaign ("price_79")
- config must have a field "pages" - object, in which fields - moodle pages (with get_parameters) and values - necessary utm metrics in format utm1=value1&utm2=value2 , where utm1 - utm local name (from metrics field)  
';

$string['config:price_start_page'] = 'Started page for price test';
$string['config:price_start_page_desc'] = 'If user will used google parameters, but don\'t have this page, he will not included in AB testing';

$string['config:available_campaign'] = 'Available AB google campaigns';
$string['config:available_campaign_desc'] = 'If campaign is restricted, then we will remove utm metrics from url.';

$string['profile_field:ab_testing'] = 'AB testing info';
$string['profile_field:ab_testing_desc'] = 'This field contains information about user AB testing in JSON format.';

$string['premium_item3'] = 'FAANG Sourced Interview Questions';