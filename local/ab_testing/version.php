<?php

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_ab_testing
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */

$plugin->component = 'local_ab_testing';
$plugin->release = '0.1.0';
$plugin->version = 2024021200;
$plugin->requires = 2022112800;
$plugin->maturity = MATURITY_ALPHA;
$plugin->dependencies = [
    'auth_stripe' => 2024013001
];
