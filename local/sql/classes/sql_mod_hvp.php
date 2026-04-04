<?php


namespace local_sql;
use auth_stripe\output\stripe\user_tier_output;

require_once ($CFG->dirroot.'/local/sql/lib.php');

class sql_mod_hvp
{
    /**
     * @param $cm
     * @return mixed
     * @throws \moodle_exception
     */
    static public function view_block($cm)
    {
        global $COURSE, $OUTPUT, $USER;
        $modinfo = get_fast_modinfo($COURSE);
        $sections = $modinfo->get_sections();
        $ss = $modinfo->get_section_info_by_id($cm->section);
        $results = [];
        $count_video_item = 1;
        $completion = new \completion_info($COURSE);
        $results['preloader_url'] = $OUTPUT->image_url('loader', 'theme_sql')->out();
        foreach ($sections[$ss->section] as $section_id) {
            $activity = $modinfo->get_cm($section_id);

            if (!in_array($activity->modname, ['hvp'])) {
                continue;
            }
            $data = $completion->get_data($activity, false, $USER->id);
            $class = "state-{$data->completionstate} ";
            if (in_array($data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                $class .= 'done ';
            }
            if ($cm->id == $activity->id) {
                $class .= 'current ';
            }
            $cm_url = $activity->get_url();
            if (!\theme_sql\sql_access_to_premium::has_access($count_video_item, $COURSE->id)) {
                if (user_tier_output::is_wait_onboarding($USER->id) == 1) {
                    $class .= 'disabled ';
                } else {
                    $class .= 'locked upgrade_plan ';
                }

                $cm_url = '#';
            } else {
                if (!$activity->available){
                    $class .= 'disabled ';
                    $cm_url = '#';
                }
            }

            $results['data'][] = [
                'id' => $count_video_item,
                'name' => $activity->get_name(),
                'url' => $cm_url,
                'class' => $class,
                'poster_url' => get_mod_hvp_poster_url($activity->id,$activity->instance,null),
            ];
            $count_video_item++;
        }
        return $OUTPUT->render_from_template('theme_sql/mod/hvp_slider', $results);
    }
}