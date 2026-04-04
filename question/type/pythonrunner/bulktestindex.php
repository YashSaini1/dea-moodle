<?php
// This file is part of CodeRunner - http://coderunner.org.nz
//
// CodeRunner is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunner is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script provides an index for running the question tests in bulk.
 * [A modified version of the script in qtype_stack with the same name.]
 *
 * @package   qtype_pythonrunner
 * @copyright 2016, 2017 Richard Lobb, The University of Canterbury
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/pythonrunner/lib.php');

// Login and check permissions.
$context = context_system::instance();
require_login();

$PAGE->set_url('/question/type/pythonrunner/bulktestindex.php');
$PAGE->set_context($context);
$PAGE->set_title(pythonrunner_str('bulktestindextitle'));

// Create the helper class.
$bulktester = new qtype_pythonrunner_bulk_tester();

// Display.
echo $OUTPUT->header();
echo $OUTPUT->heading(pythonrunner_str('pythonrunnercontexts'));

// Find in which contexts the user can edit questions.
$questionsbycontext = $bulktester->get_num_pythonrunner_questions_by_context();
$availablequestionsbycontext = array();
foreach ($questionsbycontext as $contextid => $numpythonrunnerquestions) {
    $context = context::instance_by_id($contextid);
    if (has_capability('moodle/question:editall', $context)) {
        $name = $context->get_context_name(true, true);
        if (strpos($name, 'Quiz:') === 0) { // Quiz-specific question category.
            $course = $context->get_course_context(false);
            if ($course === false) {
                $name = 'UnknownCourse: ' . $name;
            } else {
                $name = $course->get_context_name(true, true) . ': ' . $name;
            }
        }
        $availablequestionsbycontext[$name] = array(
            'contextid' => $contextid,
            'numquestions' => $numpythonrunnerquestions);
    }
}

ksort($availablequestionsbycontext);

// List all contexts available to the user.
if (count($availablequestionsbycontext) == 0) {
    echo html_writer::tag('p', sqlrunner_str('unauthorisedbulktest'));
} else {
    echo html_writer::start_tag('ul');
    $buttonstyle = 'border: 1px solid gray; padding: 2px 2px 0px 2px;';
    $buttonstyle = 'border: 1px solid #F0F0F0; background-color: #FFFFC0; padding: 2px 2px 0px 2px;border: 4px solid white';
    foreach ($availablequestionsbycontext as $name => $info) {
        $contextid = $info['contextid'];
        $numpythonrunnerquestions = $info['numquestions'];

        $testallurl = new moodle_url('/question/type/pythonrunner/bulktest.php', array('contextid' => $contextid));
        $testalllink = html_writer::link($testallurl,
                pythonrunner_str('bulktestallincontext'),
                array('title' => pythonrunner_str('testalltitle'),
                       'style' => $buttonstyle));
        $expandlink = html_writer::link('#expand',
            pythonrunner_str('expand'),
                array('class' => 'expander',
                      'title' => pythonrunner_str('expandtitle'),
                      'style' => $buttonstyle));
        $litext = $name . ' (' . $numpythonrunnerquestions . ') ' . $testalllink . ' ' . $expandlink;
        if (strpos($name, 'Quiz:') === 0) {
            $class = 'bulktest pythonrunner context quiz';
        } else {
            $class = 'bulktest pythonrunner context normal';
        }

        if (strpos($name, ": Quiz: ") === false) {
            $class = 'bulktest pythonrunner context normal';
        } else {
            $class = 'bulktest pythonrunner context quiz';
        }
        echo html_writer::start_tag('li', array('class' => $class));
        echo $litext;

        $categories = $bulktester->get_categories_for_context($contextid);
        echo html_writer::start_tag('ul', array('class' => 'expandable'));
        foreach ($categories as $cat) {
            if ($cat->count > 0) {
                $url = new moodle_url('/question/type/pythonrunner/bulktest.php',
                                    array('contextid' => $contextid, 'categoryid' => $cat->id));
                $linktext = $cat->name . ' (' . $cat->count . ')';
                $link = html_writer::link($url, $linktext, array('style' => $buttonstyle));
                echo html_writer::tag('li', $link,
                        array('title' => pythonrunner_str('testallincategory')));
            }
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    if (has_capability('moodle/site:config', context_system::instance())) {
        echo html_writer::tag('p', html_writer::link(
                new moodle_url('/question/type/pythonrunner/bulktestall.php'), pythonrunner_str('bulktestrun')));
    }
}

echo <<<SCRIPT_END
<script>
document.addEventListener("DOMContentLoaded", function(event) {
    var expandables = document.getElementsByClassName('expandable');
    Array.from(expandables).forEach(function (expandable) {
        expandable.style.display = 'none';
    });
    var expanders = document.getElementsByClassName('expander');
    Array.from(expanders).forEach(function(expander) {
        expander.addEventListener('click', function(event) {
            event.preventDefault();
            if (expander.innerHTML == 'Expand') {
                expander.innerHTML = 'Collapse';
                expander.nextSibling.style.display = 'inline';
            } else {
                expander.innerTHML = 'Expand';
                expander.nextSibling.style.display = 'none';
            }
        });
    });
});
</script>
SCRIPT_END;

echo $OUTPUT->footer();
