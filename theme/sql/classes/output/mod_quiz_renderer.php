<?php

/**
 * Renderers for outputting parts of the question engine.
 *
 * @package    moodlecore
 * @subpackage questionengine
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_sql\output;

use auth_stripe\model\user_tier;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\subscription\tier_processor;
use html_writer;
use qbehaviour_renderer;
use qtype_renderer;
use question_attempt;
use quiz_access_manager;
use quiz_attempt;
use quiz_nav_panel_base;

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * This renderer controls the overall output of questions. It works with a
 * {@link qbehaviour_renderer} and a {@link qtype_renderer} to output the
 * type-specific bits. The main entry point is the {@link question()} method.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_renderer extends \mod_quiz_renderer
{

    /**
     * @param object $course
     * @param object $quiz
     * @param object $cm
     * @param object $context
     * @param \mod_quiz_view_object $viewobj
     *
     * @return string
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj)
    {
        global $quizobj, $OUTPUT, $PAGE;

        $per_page = 10;
        $current_page = optional_param('sql_page', 0, PARAM_INT);

        $PAGE->requires->js_call_amd('theme_sql/editor_popup', 'init');

        $id = reset($viewobj->attemptobjs);
        try {
            if (!$id){
                /* If there is no attempt, then we create */
                $timenow = time();
                $accessmanager = $quizobj->get_access_manager($timenow);
                list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
                    quiz_validate_new_attempt($quizobj, $accessmanager, true, -1, true);
                $attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);
                $attemptid = $attempt->id;
            } else {
                $attemptid = $id->get_attemptid();
            }
        } catch (\Exception $e){
            return get_string('not_questions', 'theme_sql');
        }

        $attemptobj = \quiz_attempt::create($attemptid);
        $questions_count = count($attemptobj->get_slots());
        $questions = $this->get_questions($attemptobj, $current_page * $per_page, $per_page);
        $viewobj->buttontext = get_string('next_question', 'theme_sql');
        $output = $this->view_page_tertiary_nav($viewobj);

        $template_data = compact('questions');
        $output .= $OUTPUT->render_from_template('theme_sql/mod/quiz_view', $template_data);

        if ($questions_count > $per_page){
            $output .= $OUTPUT->sql_paging_bar($questions_count, $current_page, $per_page, $this->page->url, false, 'sql_page', 3);
        }

        return $output;
    }

    /**
     * @param quiz_attempt $attemptobj
     * @param int $start_question
     * @param int $per_page
     *
     * @return array
     */
    public function get_questions($attemptobj, $start_question = 0, $per_page = 10) {
        global $DB, $USER;
        $free_questions_count = \local_sql\moodle\course_customfield::get_number_of_free_questions($attemptobj->get_courseid());
        $questions = $categories = [];
        $available_states = [
            'done'    => get_string('done', 'theme_sql'),
            'todo'    => get_string('todo', 'theme_sql'),
            'blocked' => get_string('questiondependsonprevious', 'quiz'),
            'locked'  => get_string('locked', 'theme_sql'),
            'onboarding' => get_string('onboarding','availability_sql_premium')
        ];

        // Position of processes question. Necessary for locked status
        $current_question = 0;

        // Displayed question counter
        $current_questions_obj = 0;
        $user_has_premium = tier_processor::user_has_tier(user_tier::PREMIUM_TIER);
        foreach ($attemptobj->get_slots() as $slot){
            // start displaying questions after a certain one, depending on the page
            if ($start_question > $current_question){
                $current_question++;
                continue;
            }

            if ($per_page <= $current_questions_obj){
                break;
            }

            $qa = $attemptobj->get_question_attempt($slot);
            $qa_state = $qa->get_state();
            $question = $qa->get_question();

            $page = $attemptobj->get_question_page($slot);
            $url = $this->get_question_url($page, $slot, $attemptobj);

            if ($current_question >= $free_questions_count && !$user_has_premium){
                if (user_tier_output::is_wait_onboarding($USER->id) == 1) {
                    $state = 'onboarding';
                } else {
                    $state = 'locked';
                }

                $class = 'locked upgrade_plan';
                $url = false;
            } elseif ($attemptobj->is_blocked_by_previous_question($slot)) {
                $state = $class = 'blocked';
                $url = false;
            } elseif ($qa_state == \question_state::$complete || $qa_state == \question_state::$gradedright) {
                $state = $class = 'done';
            } else {
                $state = $class = 'todo';
            }

            if (!isset($categories[$question->category])){
                $categories[$question->category] = $DB->get_record('question_categories', ['id' => $question->category]);
            }

            $name = $question->name;
            if (!empty($url)){
                $name = \html_writer::link($url, $name);
            }

            $questions[] = [
                'id'          => 'quiz-question-'.$slot,
                'class'       => $class,
                'number'      => $attemptobj->get_question_number($slot),
                'name'        => $name,
                'category'    => $categories[$question->category]->name,
                'statestring' => $available_states[$state],
                'page'        => $page,
            ];

            $current_questions_obj++;
            $current_question++;
        }
        return $questions;
    }

    /**
     * @param question_attempt $qa
     * @param $showcorrectness
     * @return \lang_string|string
     * @throws \coding_exception
     */
    protected function get_state_string(question_attempt $qa, $showcorrectness)
    {
        if ($qa->get_question(false)->length > 0) {
            return $qa->get_state_string($showcorrectness);
        }

        // Special case handling for 'information' items.
        if ($qa->get_state() == \question_state::$todo) {
            return get_string('notyetviewed', 'quiz');
        } else {
            return get_string('viewed', 'quiz');
        }
    }

    /**
     * @param $page
     * @param $slot
     * @param $attemptobj
     * @return |null
     */
    public function get_question_url($page, $slot, $attemptobj)
    {
        if ($attemptobj->can_navigate_to($slot)) {
            return $attemptobj->attempt_url($slot, $page, -1);
        } else {
            return null;
        }
    }

    /**
     * Attempt Page
     *
     * @param quiz_attempt $attemptobj Instance of quiz_attempt
     * @param int $page Current page number
     * @param quiz_access_manager $accessmanager Instance of quiz_access_manager
     * @param array $messages An array of messages
     * @param array $slots Contains an array of integers that relate to questions
     * @param int $id The ID of an attempt
     * @param int $nextpage The number of the next page
     * @return string HTML to output.
     */
    public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $nextpage) {
        $this->_setup_page($attemptobj, $slots);
        $output = '';
        $output .= $this->header();
        //$output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
        $output .= $this->quiz_notices($messages);
        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        $output .= $this->footer();
        return $output;
    }

    /**
     * @param $attemptobj
     * @param $slots
     */
    protected function _setup_page($attemptobj, $slots){
        $this->page->set_pagelayout('questioneditor');

        $slot = reset($slots);
        $name = $attemptobj->get_question_name($slot);
        $this->page->set_title($name);
        $this->page->navbar->add($name);
        $this->page->set_heading('', 1);
    }

    /**
     * Ouputs the form for making an attempt
     *
     * @param quiz_attempt $attemptobj
     * @param int $page Current page number
     * @param array $slots Array of integers relating to questions
     * @param int $id ID of the attempt
     * @param int $nextpage Next page number
     */
    public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) {
        $output = '';

        $slot = reset($slots);
        $qtype = $attemptobj->get_question_type_name($slot);
        if (!theme_sql_is_custom_question_type($qtype)){
            return parent::attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        }

        // Start the form.
        $output .= html_writer::start_tag('form',
            array('action' => new \moodle_url($attemptobj->processattempt_url(),
                array('cmid' => $attemptobj->get_cmid())), 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $output .= html_writer::start_tag('div');

        // Print all the questions.
        foreach ($slots as $slot) {
            $output .= $attemptobj->render_question($slot, false, $this,
                $attemptobj->attempt_url($slot, $page));
        }

        // Some hidden fields to trach what is going on.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'attempt',
            'value' => $attemptobj->get_attemptid()));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'thispage',
            'value' => $page, 'id' => 'followingpage'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'nextpage',
            'value' => $nextpage));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'timeup',
            'value' => '0', 'id' => 'timeup'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey',
            'value' => sesskey()));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'scrollpos',
            'value' => '', 'id' => 'scrollpos'));

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots',
            'value' => implode(',', $attemptobj->get_active_slots($page))));

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $output .= $this->connection_warning();

        return $output;
    }

    /**
     * Outputs the navigation block panel
     *
     * @param quiz_nav_panel_base $panel instance of quiz_nav_panel_base
     */
    public function navigation_panel(quiz_nav_panel_base $panel) {
        $output = '';
        $userpicture = $panel->user_picture();
        if ($userpicture) {
            $fullname = fullname($userpicture->user);
            if ($userpicture->size === true) {
                $fullname = html_writer::div($fullname);
            }
            $output .= html_writer::tag('div', $this->render($userpicture) . $fullname,
                array('id' => 'user-picture', 'class' => 'clearfix'));
        }
        $output .= $panel->render_before_button_bits($this);

        $bcc = $panel->get_button_container_class();
        $output .= html_writer::start_tag('div', array('class' => "qn_buttons clearfix $bcc"));
        foreach ($panel->get_question_buttons() as $button) {
            $output .= $this->render($button);
        }

        $output .= html_writer::end_tag('div');

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false, quiz_get_js_module());

        return $output;
    }

}
