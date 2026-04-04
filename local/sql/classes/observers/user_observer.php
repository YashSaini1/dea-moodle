<?php

namespace local_sql\observers;

use local_sql\moodle\role_manager;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Observer for user events
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_observer {

    /**
     * Enroll new user to all courses in default category.
     * Even if some courses is hidden.
     *
     * @author of this idea is Kirill Slyusar
     *
     * @param \core\event\user_created $event
     *
     * @return bool
     */
    public static function user_created(\core\event\user_created $event){
        $userid = $event->objectid;
        role_manager::assign_role(role_manager::STUDENT_ROLE, $userid);

        local_sql_enrol_user_to_all_courses($userid);
        update_filepicker_preference($userid);
        set_user_preference('drawer-open-block', true);

        return true;
    }
}