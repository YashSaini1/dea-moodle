<?php

/**
 * Version info.
 *
 * @package   availability_sql_premium
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2023030300;
$plugin->requires = 2022111800;
$plugin->component = 'availability_sql_premium';
$plugin->dependencies = [
    'local_sql' => 2023030100
];