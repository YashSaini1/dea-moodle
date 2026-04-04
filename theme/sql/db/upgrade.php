<?php

/**
 * Local SQL upgrade
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/db/upgradelib.php');

/**
 * Upgrade the local_sql plugin.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 */
function xmldb_theme_sql_upgrade($oldversion){
    global $DB, $CFG;

    if ($oldversion < 2023121500){
        // lib.php file already loaded here
        mkdir($CFG->dirroot.'/theme/'.THEME_NAME.'/js');
        mkdir($CFG->dirroot.PRISMJS_PATH);
    }
    return true;
}