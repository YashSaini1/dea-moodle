<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * View H5P Content
 *
 * @package    mod_hvp
 * @copyright  2016 Joubel AS <contact@joubel.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $PAGE, $DB, $CFG, $OUTPUT, $cm;

$id = required_param('id', PARAM_INT);

// Verify course context.
$cm = get_coursemodule_from_id('hvp', $id);
if (!$cm) {
    print_error('invalidcoursemodule');
}
$course = $DB->get_record('course', array('id' => $cm->course));
if (!$course) {
    print_error('coursemisconf');
}
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/hvp:view', $context);

// Set up view assets.
$view = new \theme_sql\mod_hvp\sql_view_assets($cm, $course);
$content = $view->getcontent();
$view->validatecontent();

// Configure page.
$PAGE->set_url(new \moodle_url('/mod/hvp/view.php', array('id' => $id)));
$PAGE->set_title(format_string($content['title']));

// Add H5P assets to page.
$view->addassetstopage();
$view->logviewed();

$PAGE->requires->css(new moodle_url(\mod_hvp\view_assets::getsiteroot() . '/mod/hvp/view.css'));
$PAGE->requires->js_call_amd('theme_sql/video_slider', 'init');

if (!get_user_preferences('drawer-open-block', false)){
    set_user_preference('drawer-open-block', true);
}
$PAGE->set_pagelayout('modhvp');
// Print page HTML.
echo $OUTPUT->header();
echo '<div class="clearer"></div>';

$hashub = (has_capability('mod/hvp:share', $context) && !empty(get_config('mod_hvp', 'site_uuid')) && !empty(get_config('mod_hvp', 'hub_secret')));
$isshared = $content['shared'] === '1';
$huboptionsdata = array(
  'id' => $id,
  'isshared' => $isshared
);

// Update Hub status for content before printing out messages.
if ($hashub && $isshared) {
    $newstate = hvp_update_hub_status($content);
    $synced = $newstate !== false ? $newstate : intval($content['synced']);
    $huboptionsdata['canbesynced'] = $synced !== \H5PContentHubSyncStatus::SYNCED && $synced !== \H5PContentHubSyncStatus::WAITING;
    $huboptionsdata['waitingclass'] = $synced === \H5PContentHubSyncStatus::WAITING ? '' : ' hidden';
    $huboptionsdata['token'] = \H5PCore::createToken('share_' . $id);
}

// Print any messages.
\mod_hvp\framework::printMessages('info', \mod_hvp\framework::messages('info'));
\mod_hvp\framework::printMessages('error', \mod_hvp\framework::messages('error'));

if ($hashub) {
    echo $OUTPUT->render_from_template('mod_hvp/hub_options', $huboptionsdata);
}
echo \local_sql\sql_mod_hvp::view_block($cm);
$view->outputview();

echo $OUTPUT->heading(format_string($content['title']));
echo \local_sql\lib\documentation::render_button_from_cm($cm);
echo $OUTPUT->footer();
die;