<?php

/**
 * Local SQL cache definitions.
 *
 * @package    local
 * @category   sql
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$definitions = array(

    \local_sql\moodle\role_manager::ROLES_CACHE_AREA => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
    ),
);