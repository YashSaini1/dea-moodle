<?php

namespace local_sql\task;

use local_sql\coaching;
use local_sql\moodle\course_customfield;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Created course task
 */
class course_updated_task extends \core\task\adhoc_task {

    public function get_name(){
        return get_string('task:course_updated', 'local_sql');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        $data = (array)$this->get_custom_data();
        $courseid = $data['courseid'];
        $coaching = $data[course_customfield::COACHING_COURSE_FIELD];

        if(!$coaching){
            local_sql_enrol_all_users_to_course($courseid);
        } else {
            // unenrol non coaching users from coaching courses
            coaching::unenrol_from_coaching($courseid);
        }
    }
}