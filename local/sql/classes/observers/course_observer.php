<?php

namespace local_sql\observers;

use local_sql\moodle\course_customfield;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Observer for course events
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_observer {

    /**
     * Enroll all global users to new course if this course is in default category.
     * Even if course is hidden.
     *
     * @author of this idea is Kirill Slyusar
     *
     * @param \core\event\course_created $event
     *
     * @return bool
     */
    public static function course_created(\core\event\course_created $event){
        $adhocktask = new \local_sql\task\course_created_task();
        $adhocktask->set_custom_data(['courseid' => $event->courseid]);
        \core\task\manager::queue_adhoc_task($adhocktask);
        return true;
    }

    /**
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event){
        $updated = $event->other['updatedfields'];
        $field = 'customfield_'.course_customfield::COACHING_COURSE_FIELD;
        if (array_key_exists($field, $updated)){
            $adhocktask = new \local_sql\task\course_updated_task();
            $adhocktask->set_custom_data(['courseid' => $event->courseid, course_customfield::COACHING_COURSE_FIELD => $updated[$field]]);
            \core\task\manager::queue_adhoc_task($adhocktask);
        }
    }
}