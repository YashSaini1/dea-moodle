<?php

namespace theme_sql\output\core;

use local_sql\lib\documentation;

class course_renderer extends \core_course_renderer
{
    public function render_activity_information(\core_course\output\activity_information $page)
    {
        $data = $page->export_for_template($this->output);
        $statuscomplete = 0;
        $statusincomplete = 0;
        foreach ($data->completiondetails as $completiondetail) {
            if ($completiondetail->statuscomplete) {
                $statuscomplete++;
            }

            if ($completiondetail->statusincomplete) {
                $statusincomplete++;
            }
        }
        $completiondetail_obj = new \stdClass();
        $completiondetail_obj->statuscomplete = count($data->completiondetails) == $statuscomplete;
        $completiondetail_obj->statusincomplete = ($completiondetail_obj->statuscomplete) ? false : count($data->completiondetails) == $statusincomplete;
        $data->completiondetails = [$completiondetail_obj];
        $data->modulepage = true;
        return $this->output->render_from_template('core_course/activity_info', $data);
    }

    public function render_activity_navigation(\core_course\output\activity_navigation $page){
        $data = $page->export_for_template($this->output);
        if (isset($data->prevlink)){
            $data->prevlink->text = 'Previous activity';
        }
        if (isset($data->nextlink)){
            $data->nextlink->text = 'Next activity';
        }

        unset($data->activitylist);
        $cm = $this->page->cm;
        if ($cm && $cm->modname == 'url'){
            $data->middle_button = documentation::render_button_from_cm($cm, true);
        }
        return $this->output->render_from_template('theme_sql/mod/activity_navigation', $data);
    }
}