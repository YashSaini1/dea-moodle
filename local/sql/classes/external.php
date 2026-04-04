<?php

namespace local_sql;

use completion_info;
use external_function_parameters;
use external_single_structure;
use external_value;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/completionlib.php");

class external extends \external_api {

    public static function track_hvp_video_parameters(){
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'cmid', VALUE_REQUIRED),
                'render_navigation' => new external_value(PARAM_BOOL, 'render_navigation', VALUE_OPTIONAL),
            )
        );
    }

    public static function track_hvp_video($cmid, $render_navigation = false){
        $cm = get_coursemodule_from_id('', $cmid);
        if (empty($cm) && $cm->modname != 'hvp' && $cm->modname != 'url'){
            $result = ['status' => false];
            return $result;
        }

        // Completion.
        $completion = new completion_info(get_course($cm->course));
        $completion->set_module_viewed($cm);

        $result = ['status' => true];
        if($render_navigation){
            global $OUTPUT;
            require_login($cm->course, true, $cm);
            $result['rendered_navigation'] = $OUTPUT->activity_navigation();
            return $result;
        }

        [, $cm] = get_course_and_cm_from_cmid($cmid, $cm->modname);
        $course_modinfo = $cm->get_modinfo();
        $cms = $course_modinfo->get_cms();
        $current = $next = null;
        foreach ($cms as $cm_item){
            if ($current){
                $next = $cm_item;
                break;
            }
            if ($cm->id == $cm_item->id){
                $current = $cm_item;
            }
        }

        if (!empty($next) && !empty($next->available)){
            $result['available_next'] = $next->get_url()->out();
        }

        return $result;
    }

    public static function track_hvp_video_returns(){
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'available_next' => new external_value(PARAM_URL, 'available_next: true if success',VALUE_OPTIONAL),
                'rendered_navigation' => new external_value(PARAM_RAW, 'rendered activity_navigation html',VALUE_OPTIONAL),
            )
        );
    }
}