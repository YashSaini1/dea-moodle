<?php

namespace theme_sql\mod_quiz;

use mod_quiz_display_options;
use quiz_access_manager;

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * A class encapsulating a quiz and the questions it contains, and making the
 * information available to scripts like view.php.
 *
 * Initially, it only loads a minimal amout of information about each question - loading
 * extra information only when necessary or when asked. The class tracks which questions
 * are loaded.
 *
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */
class theme_sql_quiz extends \quiz {

    /**
     * Static function to create a new quiz object for a specific user.
     *
     * @param int $quizid the the quiz id.
     * @param int|null $userid the the userid (optional). If passed, relevant overrides are applied.
     *
     * @return \theme_sql\mod_quiz\theme_sql_quiz the new quiz object.
     */
    public static function create($quizid, $userid = null) {
        global $DB;

        $quiz = quiz_access_manager::load_quiz_and_settings($quizid);
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

        // Update quiz with override information.
        if ($userid) {
            $quiz = quiz_update_effective_access($quiz, $userid);
        }

        return new static($quiz, $cm, $course);
    }

    /**
     * Create a {@link quiz_attempt} for an attempt at this quiz.
     *
     * @param object $attemptdata row from the quiz_attempts table.
     * @return theme_sql_quiz_attempt the new quiz_attempt object.
     */
    public function create_attempt_object($attemptdata) {
        return new theme_sql_quiz_attempt($attemptdata, $this->quiz, $this->cm, $this->course);
    }

    // Functions for loading more data =========================================

    /**
     * Fully load some or all of the questions for this quiz. You must call
     * {@link preload_questions()} first.
     *
     * @param array|null $deprecated no longer supported (it was not used).
     */
    public function load_questions($deprecated = null) {
        if ($deprecated !== null) {
            debugging('The argument to quiz::load_questions is no longer supported. ' .
                'All questions are always loaded.', DEBUG_DEVELOPER);
        }
        if ($this->questions === null) {
            throw new \coding_exception('You must call preload_questions before calling load_questions.');
        }

        $questionstoprocess = [];
        foreach ($this->questions as $question) {
            if (is_number($question->questionid)) {
                $question->id = $question->questionid;
                $questionstoprocess[$question->questionid] = $question;
            }
        }
        get_question_options($questionstoprocess);
    }

    /**
     * Get an instance of the {@link \mod_quiz\structure} class for this quiz.
     * @return \mod_quiz\structure describes the questions in the quiz.
     */
    public function get_structure() {
        return theme_sql_structure::create_for_quiz($this);
    }

    /**
     * Checks user enrollment in the current course.
     *
     * @param int $userid the id of the user to check.
     * @return bool whether the user is enrolled.
     */
    public function is_participant($userid) {
        return is_enrolled($this->get_context(), $userid, 'mod/quiz:attempt', $this->show_only_active_users());
    }

    /**
     * Check is only active users in course should be shown.
     *
     * @return bool true if only active users should be shown.
     */
    public function show_only_active_users() {
        return !has_capability('moodle/course:viewsuspendedusers', $this->get_context());
    }

    // Bits of content =========================================================

    /**
     * If $reviewoptions->attempt is false, meaning that students can't review this
     * attempt at the moment, return an appropriate string explaining why.
     *
     * @param int $when One of the mod_quiz_display_options::DURING,
     *      IMMEDIATELY_AFTER, LATER_WHILE_OPEN or AFTER_CLOSE constants.
     * @param bool $short if true, return a shorter string.
     * @return string an appropraite message.
     */
    public function cannot_review_message($when, $short = false) {
        if ($short) {
            $langstrsuffix = 'short';
            $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        } else {
            $langstrsuffix = '';
            $dateformat = '';
        }

        if ($when == mod_quiz_display_options::DURING ||
            $when == mod_quiz_display_options::IMMEDIATELY_AFTER) {
            return '';
        } else if ($when == mod_quiz_display_options::LATER_WHILE_OPEN && $this->quiz->timeclose &&
            $this->quiz->reviewattempt & mod_quiz_display_options::AFTER_CLOSE) {
            return get_string('noreviewuntil' . $langstrsuffix, 'quiz',
                userdate($this->quiz->timeclose, $dateformat));
        } else {
            return get_string('noreview' . $langstrsuffix, 'quiz');
        }
    }

    /**
     * Get and save quiz slots
     *
     * @return array
     */
    public function get_slots(){
        static $slots = null;
        if(is_null($slots)){
            global $DB;
            $slots = $DB->get_records('quiz_slots', array('quizid' => $this->get_quizid()), 'slot', 'slot, id, requireprevious');
        }

        return $slots;
    }
}