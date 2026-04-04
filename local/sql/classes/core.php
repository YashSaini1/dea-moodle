<?php

namespace local_sql;

use local_sql\core\logger;
use local_sql\core\util;
use local_sql\core\moodle;
use local_sql\moodle\role_manager;

class core {
    use moodle, util, logger;

    const LOCAL_SQL = 'local_sql';

    const PLUGIN_NAME = self::LOCAL_SQL;
    const PLUGIN_PATH = 'local/sql';

    public static function enrol_user_to_courses($user_or_id, $courses){
        $userid = static::get_userid($user_or_id);

        $timeenrol = time();
        $role = role_manager::get_student_role();
        $manual = enrol_get_plugin('manual');
        foreach ($courses as $course){
            $enrols = enrol_get_instances($course->id, true);
            if (empty($enrols)) continue;

            foreach ($enrols as $enrol){
                $manual->enrol_user($enrol, $userid, $role->id, $timeenrol);
            }
        }
    }

    public static function unenrol_user_from_courses($user_or_id, $courses){
        $userid = static::get_userid($user_or_id);

        $manual = enrol_get_plugin('manual');
        foreach ($courses as $course){
            $enrols = enrol_get_instances($course->id, true);
            if (empty($enrols)) continue;

            foreach ($enrols as $enrol){
                $manual->unenrol_user($enrol, $userid);
            }
        }
    }

    public static function get_support_mail(){
        return get_config('core', 'supportemail');
    }

    public static function redirect_to_profile(){
        global $CFG;
        redirect($CFG->wwwroot.'/user/profile.php');
    }

    public static function log_message($message) {
        static::info($message);
    }
}