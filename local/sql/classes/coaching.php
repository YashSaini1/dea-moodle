<?php

namespace local_sql;

use local_sql\moodle\course_customfield;
use local_sql\moodle\role_manager;

class coaching {

    static public function get_courses(){
        static $courses = null;
        if (is_null($courses)){
            $courses = static::_load_coaching_courses();
        }

        return $courses;
    }

    static public function remove_unvisible($courses) {
        return array_filter($courses, function ($course) {
            return !isset($course->visible) || $course->visible != 0;
        });
    }

    static protected function _load_coaching_courses(){
        $field = course_customfield::get_field_instance(course_customfield::COACHING_COURSE_FIELD);
        if (empty($field)){
            return [];
        }

        global $DB;
        $sql = "SELECT c.*
                FROM {customfield_data} cd
                JOIN {course} c ON c.id = cd.instanceid
                WHERE cd.fieldid= :fieldid AND cd.intvalue= :intvalue";
        $params = ['fieldid' => $field->get('id'), 'intvalue' => 1];
        return $DB->get_records_sql($sql, $params);
    }

    public static function is_coaching_course($courseid): bool{
        $courses = static::get_courses();
        return !empty($courses[$courseid]);
    }

    public static function unenrol_from_coaching($courseid){
        $ctx = \context_course::instance($courseid);
        $role_names = [
            role_manager::MOODLE_STUDENT_ROLE,
            role_manager::STUDENT_ROLE,
        ];

        $manual = enrol_get_plugin('manual');
        $enrols = enrol_get_instances($courseid, true);
        if (empty($enrols)) return;

        $system_users = \local_sql\moodle\user::get_users_with_role($role_names, $ctx);
        foreach ($system_users as $userinfo){
            if (!coaching::has_coaching($userinfo->userid)){
                foreach ($enrols as $enrol){
                    $manual->unenrol_user($enrol, $userinfo->userid);
                }
            }
        }
    }

    public static function enrol_user($user_or_id = null){
        $coaching_courses = static::get_courses();
        \local_sql\core::enrol_user_to_courses($user_or_id, $coaching_courses);
    }

    public static function unenrol_user($userid){
        $coaching_courses = static::get_courses();
        \local_sql\core::unenrol_user_from_courses($userid, $coaching_courses);
    }

    public static function has_coaching($user_or_id = null){
        return role_manager::is_admin($user_or_id) || role_manager::is_coaching_student($user_or_id);
    }

    public static function add_coaching_role($user_or_id = null){
        if (static::has_coaching($user_or_id)){
            return;
        }

        role_manager::assign_role(role_manager::COACHING_ROLE, $user_or_id);
        static::enrol_user($user_or_id);
    }

    public static function remove_coaching_role($user_or_id = null){
        if (!static::has_coaching($user_or_id)){
            return;
        }
        role_manager::unassign_role(role_manager::COACHING_ROLE, $user_or_id);
        static::unenrol_user($user_or_id);
    }
}