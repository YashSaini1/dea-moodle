<?php

/**
 * @package     qtype_pythonrunner
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2023041000;
$plugin->requires = 2022041900;
$plugin->cron = 0;
$plugin->component = 'qtype_pythonrunner';
$plugin->maturity = MATURITY_RC;
$plugin->release = '5.0.0';

$plugin->dependencies = array(
    'qtype_sqlrunner' => 2023040400,
);