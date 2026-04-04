<?php

/**
 * Theme plugin
 *
 * @package     theme_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */

$plugin->component = 'theme_sql';
$plugin->version = 2023121500;

$plugin->requires = 2022041904.11;
$plugin->dependencies = [
    'theme_boost' => 2022041900,
    'auth_stripe' => 2023051900
];

// This is a stable release.
$plugin->maturity = MATURITY_STABLE;

// This is the named version.
$plugin->release = 1.0;