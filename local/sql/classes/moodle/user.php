<?php

namespace local_sql\moodle;

class user {

    /**
     * Get all "our" users, who has custom global role
     *
     * @param array $role_names - all rolenames
     * @param \context $ctx - roles context
     * @param bool $load_user - load user object or not
     *
     * @return array
     */
    public static function get_users_with_role($role_names = null, $ctx = null, $load_user = false){
        global $DB;
        // empty array too
        if (empty($role_names)){
            $role_names = [
                role_manager::MOODLE_STUDENT_ROLE,
                role_manager::STUDENT_ROLE,
                role_manager::ADMIN_ROLE,
            ];
        }

        $roles = role_manager::get_roles($role_names);
        [$role_sql, $params] = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED, 'role');

        if (!empty($ctx)){
            $contextid = $ctx->id;
        } else {
            $contextid = SYSCONTEXTID;
        }

        $params['contextid'] = $contextid;
        $fields = '';
        if ($load_user){
            $fields = ',u.*';
        }

        $sql = "SELECT DISTINCT ra.id as raid, ra.userid as userid, ra.roleid as roleid $fields
            FROM {role_assignments} ra
            JOIN {user} u ON u.id = ra.userid AND u.suspended = 0 AND u.deleted = 0
            WHERE ra.contextid=:contextid AND ra.roleid $role_sql";
        return $DB->get_records_sql($sql, $params);
    }
}