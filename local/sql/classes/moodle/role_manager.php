<?php

namespace local_sql\moodle;

use context_system;
use local_sql\core;

class role_manager {

    const ROLES_CACHE_AREA = 'roles';

    const MOODLE_STUDENT_ROLE = 'student';

    const STUDENT_ROLE = 'sql_student';

    const ADMIN_ROLE = 'sql_admin';

    const COACHING_ROLE = 'coaching_student';

    const SELLER_ROLE = 'seller';

    protected array $roles = [];

    /// Static
    public static function instance(){
        static $instance = null;
        if(is_null($instance)){
            $instance = new static();
        }
        return $instance;
    }

    public static function DB(): \moodle_database{
        global $DB;
        return $DB;
    }

    public static function purge_caches(){
        $cache = \local_sql\core::get_cache(static::ROLES_CACHE_AREA);
        $cache->purge();
    }

    protected static function _cache($name, $value = null){
        static $cache = null;
        if (is_null($cache)){
            $cache = \local_sql\core::get_cache(static::ROLES_CACHE_AREA);
        }

        if (is_null($value)){
            // if no cached data $cache->get() return false
            // but we cannot detect this case, because if $cached = 0, expression !$cached returns true
            // cast false value to null (we do not cache bool here i hope)
            $cached = $cache->get($name);
            if (is_bool($cached) && !$cached){
                return null;
            }
            return $cached;
        }

        $cache->set($name, $value);
        return true;
    }

    public static function get_role($shortname){
        $cached = static::_cache($shortname);
        if (is_null($cached)){
            $cached = static::DB()->get_record('role', ['shortname' => $shortname]);
            static::_cache($shortname, $cached);
        }

        return $cached;
    }

    public static function get_roles($shortnames){
        $result = [];
        foreach ($shortnames as $shortname){
            $role = static::get_role($shortname);
            if (empty($role)){
                debugging('Role '.$shortname.' is missing');
                continue;
            }
            $result[$role->id] = $role;
        }
        return $result;
    }

    public static function assign_role($rolename, $user_or_id, $ctx = null){
        $ctx = $ctx ?? \context_system::instance();
        $role = static::get_role($rolename);
        $userid = core::get_userid($user_or_id);
        if(!empty($role)){
            role_assign($role->id, $userid, $ctx->id);
        }
    }

    public static function unassign_role($role_or_name, $userid, $ctx = null){
        $ctx = $ctx ?? \context_system::instance();
        if (is_string($role_or_name)){
            $role = static::get_role($role_or_name);
        } else {
            $role = $role_or_name;
        }

        if(!empty($role)){
            role_unassign($role->id, $userid, $ctx->id);
        }
    }

    public static function get_student_role(){
        $sql_student_role = static::get_role(static::STUDENT_ROLE);
        if(!empty($sql_student_role)){
            return $sql_student_role;
        }

        return static::get_role(static::MOODLE_STUDENT_ROLE);
    }

    public static function get_admin_role(){
        return static::get_role(static::ADMIN_ROLE);
    }

    public static function get_coaching_role(){
        return static::get_role(static::COACHING_ROLE);
    }

    public static function get_seller_role(){
        return static::get_role(static::SELLER_ROLE);
    }

    public static function get_all_user_roles($userid = null){
        static $user_roles = [];

        $userid = core::get_userid($userid);
        if (!isset($user_roles[$userid])){
            $ctx = context_system::instance();
            $user_roles[$userid] = get_user_roles($ctx, $userid);
        }

        return $user_roles[$userid];
    }

    protected static function _check_user_roles($check_roles, $userid = null){
        $roles = static::get_all_user_roles($userid);

        if (is_array($check_roles)){
            foreach ($roles as $role){
                if (in_array($role->shortname, $check_roles)){
                    return true;
                }
            }
            return false;
        }

        foreach ($roles as $role){
            if ($role->shortname == $check_roles){
                return true;
            }
        }
        return false;
    }

    public static function is_student($userid = null){
        static $user_roles = [];

        $userid = core::get_userid($userid);
        if (!isset($user_roles[$userid])){
            $user_roles[$userid] = static::_check_user_roles([static::STUDENT_ROLE, static::MOODLE_STUDENT_ROLE], $userid);
        }
        return $user_roles[$userid];
    }

    public static function is_admin($user_or_id = null){
        static $user_roles = [];

        $userid = core::get_userid($user_or_id);
        if (!isset($user_roles[$userid])){
            $user_roles[$userid] = static::_check_is_admin($userid);
        }
        return $user_roles[$userid];
    }

    /**
     * @param object|numeric $user_or_id
     *
     * @return bool
     */
    public static function is_coaching_student($user_or_id = null){
        static $user_roles = [];

        $userid = core::get_userid($user_or_id);
        if (!isset($user_roles[$userid])){
            $user_roles[$userid] = static::_check_user_roles(static::COACHING_ROLE, $userid);
        }

        return $user_roles[$userid];
    }

    /**
     * @param object|numeric $user_or_id
     *
     * @return bool
     */
    public static function is_seller($user_or_id = null){
        static $user_roles = [];

        $userid = core::get_userid($user_or_id);
        if (!isset($user_roles[$userid])){
            $user_roles[$userid] = static::_check_user_roles(static::SELLER_ROLE, $userid);
        }

        return $user_roles[$userid];
    }

    protected static function _check_is_admin($userid = null): bool{
        if (is_siteadmin($userid)){
            return true;
        }

        return static::_check_user_roles(static::ADMIN_ROLE, $userid);
    }

    public static function get_users_with_role($rolename) {
        global $DB;

        $role = static::get_role($rolename);
        if (empty($role)) {
            debugging('Role ' . $rolename . ' is missing');
            return [];
        }

        $sql = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            WHERE ra.roleid = :roleid";
        $params = ['roleid' => $role->id];

        $users = $DB->get_records_sql($sql, $params);

        return $users;
    }

    public static function is_local_admin(): bool {
        global $USER;
        return static::is_admin($USER->id);
    }

    public static function is_local_coaching(): bool {
        global $USER;
        return static::is_coaching_student($USER->id);
    }

}