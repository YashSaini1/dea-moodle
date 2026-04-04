<?php

/**
 * Local SQL install
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/sql/db/upgradelib.php');

/**
 * Install the local_sql plugin callback.
 */
function xmldb_local_sql_install(){
    update_repository_sortnumber();
    create_custom_field();
    return true;
}

