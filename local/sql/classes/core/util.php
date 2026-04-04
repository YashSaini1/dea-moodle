<?php

namespace local_sql\core;

trait util {

    public static function get_user($userid){
        static $users = [];

        if (!array_key_exists($userid, $users)){
            $users[$userid] = \core_user::get_user($userid);
        }

        return $users[$userid];
    }

    public static function get_user_by_email($email){
        return \core_user::get_user_by_email($email);
    }

    public static function get_userid($user_or_id = null, $use_current = true){
        global $USER;
        if (empty($user_or_id)){
            if ($use_current){
                return $USER->id;
            }
            return 0;
        }

        return static::get_id($user_or_id);
    }

    public static function get_id($object_or_id){
        if (is_object($object_or_id)){
            return !empty($object_or_id->id) ? $object_or_id->id : 0;
        } elseif (is_numeric($object_or_id)) {
            return $object_or_id;
        }

        return 0;
    }
}