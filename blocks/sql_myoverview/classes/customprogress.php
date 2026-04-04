<?php

namespace block_sql_myoverview;

use renderer_base;

global $CFG;

require_once($CFG->dirroot.'/blocks/sql_myoverview/lib.php');

class customprogress extends \core_course\external\course_summary_exporter {

    protected function get_other_values(renderer_base $output){
        static $can_delete = null;
        if (is_null($can_delete)){
            $can_delete = has_capability('moodle/course:delete', \context_system::instance());
        }

        $other_values = parent::get_other_values($output);
        $progress_value = block_sql_myoverview_progress_modules_in_course($this->data);

        $other_values['allmodules'] = $progress_value['all_modules'];
        $other_values['completionmodules'] = $progress_value['completion_modules'];
        $other_values['candeletecourse'] = $can_delete;

        // always false, because this value setted in external.php for performance reason
        $other_values['coachingcourse'] = false;
        $other_values['disabled_coaching'] = false;
        $other_values['waitonboarding'] = false;

        return $other_values;
    }

    public static function define_other_properties(){
        $other_properties = parent::define_other_properties();
        $other_properties['allmodules'] = array('type' => PARAM_INT);
        $other_properties['completionmodules'] = array('type' => PARAM_INT);
        $other_properties['candeletecourse'] = array('type' => PARAM_BOOL);
        $other_properties['coachingcourse'] = array('type' => PARAM_BOOL);
        $other_properties['disabled_coaching'] = array('type' => PARAM_BOOL);
        $other_properties['waitonboarding'] = array('type' => PARAM_BOOL);

        return $other_properties;
    }
}