<?php

/**
 * Local Data Collector install
 *
 * @package     local_data_collector
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install the local_data_collector plugin callback.
 */
function xmldb_local_data_collector_install(){
    global $CFG;
    mkdir($CFG->dirroot .'/local/data_collector/webhook');
    return true;
}

