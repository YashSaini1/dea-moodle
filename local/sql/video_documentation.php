<?php

require_once('../../config.php');
global $OUTPUT, $PAGE;

$id = required_param('id', PARAM_INT);

/**
 * @var cm_info $cm
 */
[, $cm] = get_course_and_cm_from_cmid($id);
if (!$cm->uservisible){
    $course_ciew_url = new moodle_url('/course/view.php', ['id' => $cm->course]);
    redirect($course_ciew_url, get_string('cannot_view_module', 'local_sql'), 0, \core\output\notification::NOTIFY_ERROR);
}
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->add_body_class('limitedwidth');

$heading = get_string('documentation', 'local_sql');
$PAGE->set_url('/local/sql/video_documentation.php');
$PAGE->set_heading($heading);
$PAGE->set_title($heading);
$PAGE->set_pagelayout('modhvp');
$PAGE->set_cm($cm);
$PAGE->navbar->add($heading);

$instance = $DB->get_record($cm->modname, ['id' => $cm->instance]);

echo $OUTPUT->header();
echo $OUTPUT->box_start('mod_introbox', 'hvpintro');
echo format_module_intro($cm->modname, $instance, $cm->id);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();