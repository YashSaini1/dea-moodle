<?php

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_data_collector
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */

$plugin->component = 'local_data_collector';
$plugin->release = '0.1.0';
$plugin->version = 2023052101;
$plugin->requires = 2022112800;
$plugin->maturity = MATURITY_ALPHA;
$plugin->dependencies = [
    'auth_stripe' => 2023051900
];
