<?php

namespace local_sql\task;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Created course task
 */
class course_created_task extends \core\task\adhoc_task {

    public function get_name(){
        return get_string('task:course_created', 'local_sql');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        $data = (array)$this->get_custom_data();
        local_sql_enrol_all_users_to_course($data['courseid']);
    }
}