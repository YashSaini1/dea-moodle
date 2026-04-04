<?php

/**
 * Local AB Testing install
 *
 * @package     local_ab_testing
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use local_ab_testing\core;

defined('MOODLE_INTERNAL') || die();

/**
 * Install the local_sql plugin callback.
 */
function xmldb_local_ab_testing_install(){
    global $DB, $CFG;
    require_once($CFG->dirroot.'/user/profile/definelib.php');

    if (empty($DB->get_record('user_info_field', ['shortname' => core::PROFILE_FIELD_NAME]))){
        $data = [
            'shortname'   => core::PROFILE_FIELD_NAME,
            'name'        => core::str('profile_field:ab_testing'),
            'datatype'    => 'text',
            'description' => [
                'text'   => core::str('profile_field:ab_testing_desc'),
                'format' => 1,
            ],
            'categoryid'  => 1,
            'required'    => 0,
            'locked'      => 0,
            'visible'     => 3,
            'forceunique' => 0,
            'signup'      => 0,
            'defaultdata' => '',
            'param1'      => 30,
            'param2'      => 8192,
            'param3'      => 0,
            'param4'      => '',
            'param5'      => '',
        ];
        profile_save_field((object)$data, []);
    }
    return true;
}

