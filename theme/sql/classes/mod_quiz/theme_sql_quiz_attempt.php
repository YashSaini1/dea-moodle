<?php

/**
 * This file contains custom quiz_attempt class
 *
 * @package     theme_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_sql\mod_quiz;

use mod_quiz_renderer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * This class extends the quiz class to hold data about the state of a particular attempt,
 * in addition to the data about the quiz.
 *
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */
class theme_sql_quiz_attempt extends \quiz_attempt {

    // Constructor =============================================================
    /**
     * Constructor assuming we already have the necessary data loaded.
     *
     * @param object $attempt the row of the quiz_attempts table.
     * @param object $quiz the quiz object for this attempt and user.
     * @param object $cm the course_module object for this quiz.
     * @param object $course the row from the course table for the course we belong to.
     * @param bool $loadquestions (optional) if true, the default, load all the details
     *      of the state of each question. Else just set up the basic details of the attempt.
     *
     * @noinspection PhpMissingParentConstructorInspection*/
    public function __construct($attempt, $quiz, $cm, $course, $loadquestions = true) {
        if($quiz instanceof \quiz){
            $this->quizobj = $quiz;
        } else {
            $this->quizobj = new theme_sql_quiz($quiz, $cm, $course);
        }
        $this->attempt = $attempt;

        if ($loadquestions) {
            $this->load_questions();
        }
    }

    /**
     * This method can be called later if the object was constructed with $loadqusetions = false.
     */
    public function load_questions() {
        if (isset($this->quba)) {
            throw new \coding_exception('This quiz attempt has already had the questions loaded.');
        }

        $this->quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);
        $this->slots = $this->quizobj->get_slots();
        $this->sections = $this->quizobj->get_sections();

        $this->link_sections_and_slots();
        $this->determine_layout();
        $this->number_questions();
    }


    /**
     * Used by {create()} and {create_from_usage_id()}.
     *
     * @param array $conditions passed to $DB->get_record('quiz_attempts', $conditions).
     * @return theme_sql_quiz_attempt the desired instance of this class.
     */
    protected static function create_helper($conditions) {
        global $DB;

        $attempt = $DB->get_record('quiz_attempts', $conditions, '*', MUST_EXIST);
        $quiz = \quiz_access_manager::load_quiz_and_settings($attempt->quiz);
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

        // Update quiz with override information.
        $quiz = quiz_update_effective_access($quiz, $attempt->userid);

        return new static($attempt, $quiz, $cm, $course);
    }

    /**
     * Create attempt object from attempt record and quiz object
     *
     * @param $params array{quiz:theme_sql_quiz, attempt:object, load_questions:bool}
     *
     * @return theme_sql_quiz_attempt the desired instance of this class.
     */
    public static function create_from_params($params) {
        if(empty($params['quiz']) || empty($params['attempt'])){
            throw new \moodle_exception('empty params for theme_sql_quiz_attempt::create_from_params() method');
        }

        $attempt = $params['attempt'];
        $quiz = $params['quiz'];
        $load_questions = $params['load_questions'] ?? true;

        return new static($attempt, $quiz, $quiz->get_cm(), $quiz->get_course(), $load_questions);
    }

    /**
     * Static function to create a new quiz_attempt object given an attemptid.
     *
     * @param int $attemptid the attempt id.
     * @return \quiz_attempt the new quiz_attempt object
     */
    public static function create($attemptid) {
        return static::create_helper(array('id' => $attemptid));
    }

    /**
     * Static function to create a new quiz_attempt object given a usage id.
     *
     * @param int $usageid the attempt usage id.
     * @return theme_sql_quiz_attempt the new quiz_attempt object
     */
    public static function create_from_usage_id($usageid) {
        return static::create_helper(array('uniqueid' => $usageid));
    }

    /**
     * Get the navigation panel object for this attempt.
     *
     * @param mod_quiz_renderer $output     the quiz renderer to use to output things.
     * @param string            $panelclass The type of panel, quiz_attempt_nav_panel or quiz_review_nav_panel
     * @param int               $page       the current page number.
     * @param bool              $showall    whether we are showing the whole quiz on one page. (Used by review.php.)
     *
     * @return \block_contents the requested object.
     */
    public function get_navigation_panel(mod_quiz_renderer $output, $panelclass, $page, $showall = false){
        global $OUTPUT;
        $panel = new $panelclass($this, $this->get_display_options(true), $page, $showall);

        $bc = new \block_contents();
        $bc->attributes['id'] = 'mod_quiz_navblock';
        $bc->attributes['role'] = 'navigation';
        $bc->title = get_string('quiznavigation', 'quiz');
        $bc->content = $output->navigation_panel($panel);

        $count_records = count($this->get_slots());
        $bc->content = $OUTPUT->sql_paging_bar($count_records, $page, 1, null, 'quiz_navigation','page');
        return $bc;
    }

    /**
     * Check the existing of question attempt for each quiz questions.
     * If not exist - create it and update layout
     */
    public function check_all_question_attempts(){
        $created = false;
        $layout = [];
        foreach($this->quizobj->get_questions() as $key => $question){
            try{
                $this->get_question_attempt($question->slot);
            } catch(\Exception $e){
                $created = true;
                $questiondata = \question_bank::make_question($question);
                $newslot = $this->quba->add_question($questiondata, $question->defaultmark);

                $this->quba->start_question($question->slot);
                \question_engine::save_questions_usage_by_activity($this->quba);

                // get new qa to confirm, that attempt saved correctly
                $qa = $this->quba->get_question_attempt($newslot);
            }
            $layout[] = $question->slot;
            $layout[] = 0;
        }
        if($created){
            $this->_save_layout($layout);
        }
    }

    /**
     * Save attempt layout
     *
     * @param array|string $layout
     */
    protected function _save_layout($layout){
        $layout = is_array($layout) ? implode(',', $layout) : $layout;
        $this->attempt->layout = $layout;
        static::save_record((object)['id' => $this->attempt->id, 'layout' => $this->attempt->layout]);
    }

    /**
     * Save quiz_attempt record
     *
     * @param object $attemptrec
     */
    public static function save_record($attemptrec){
        global $DB;
        $DB->update_record('quiz_attempts', $attemptrec);
    }

    /**
     * Save many question attempts
     *
     * @param object[] $question_attempts
     */
    public static function save_question_attempts($question_attempts){
        global $DB;
        foreach ($question_attempts as $question_attempt){
            $DB->update_record('question_attempts', $question_attempt);
        }
    }

    /**
     * Process all the actions that were submitted as part of the current request.
     *
     * @param int $timestamp the timestamp that should be stored as the modified.
     *      time in the database for these actions. If null, will use the current time.
     * @param bool $becomingoverdue
     * @param array|null $simulatedresponses If not null, then we are testing, and this is an array of simulated data.
     *      There are two formats supported here, for historical reasons. The newer approach is to pass an array created by
     *      {@link core_question_generator::get_simulated_post_data_for_questions_in_usage()}.
     *      the second is to pass an array slot no => contains arrays representing student
     *      responses which will be passed to {@link question_definition::prepare_simulated_post_data()}.
     *      This second method will probably get deprecated one day.
     */
    public function process_submitted_actions($timestamp, $becomingoverdue = false, $simulatedresponses = null){
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        if ($simulatedresponses !== null){
            if (is_int(key($simulatedresponses))){
                // Legacy approach. Should be removed one day.
                $simulatedpostdata = $this->quba->prepare_simulated_post_data($simulatedresponses);
            } else {
                $simulatedpostdata = $simulatedresponses;
            }
        } else {
            $simulatedpostdata = null;
        }

        $this->quba->process_all_actions($timestamp, $simulatedpostdata);
        \question_engine::save_questions_usage_by_activity($this->quba);

        $this->attempt->timemodified = $timestamp;
        if ($this->attempt->state == self::FINISHED){
            $this->attempt->sumgrades = $this->quba->get_total_mark();
        }

        if ($becomingoverdue){
            $this->process_going_overdue($timestamp, true);
        } else {
            $DB->update_record('quiz_attempts', $this->attempt);
        }

        $completion = new \completion_info($this->get_course());
        $completion_data = $completion->get_data($this->get_cm());
        if (!$this->is_preview() && $completion_data->completionstate == COMPLETION_INCOMPLETE){
            if (count($this->quizobj->get_slots()) == $this->_get_completed_marks()){
                $completion->set_module_viewed($this->get_cm());
            }

            if ($this->attempt->state == self::FINISHED){
                quiz_save_best_grade($this->get_quiz(), $this->get_userid());
            }
        }

        $transaction->allow_commit();
    }

    /**
     * @return int Count marks. Each custom question has a 1 mark
     */
    protected function _get_completed_marks(){
        global $DB;
        $completed = \question_state::$complete.'';
        $gradedright = \question_state::$gradedright.'';
        [$state_sql, $params] = $DB->get_in_or_equal([$completed, $gradedright], SQL_PARAMS_NAMED, 'state');

        $params['questionusage'] = $this->attempt->uniqueid;
        $sql = "SELECT COUNT(DISTINCT qa.id)
                FROM {question_attempts} qa
                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id AND qas.state $state_sql
                WHERE qa.questionusageid=:questionusage";
        return $DB->count_records_sql($sql, $params);
    }

    public static function delete_old_qa_steps(){
        global $DB, $CFG;
        require_once($CFG->dirroot.'/theme/sql/quiz_lib.php');

        $sql = "SELECT qas.id AS stepid, qa.id AS attemptid, qatt.slot, qas.questionattemptid AS questionattemptid
                FROM mdl_question_attempt_steps qas
                JOIN mdl_question_attempts qatt ON qatt.id = qas.questionattemptid
                JOIN mdl_quiz_attempts qa ON qa.uniqueid = qatt.questionusageid
                WHERE qas.state != 'complete'
                  AND NOT (fraction IS NULL OR FLOOR(fraction) = 1)
                ORDER BY questionattemptid, sequencenumber";

        $records = $DB->get_records_sql($sql);
        if (empty($records)){
            return;
        }
        $trans = $DB->start_delegated_transaction();
        $previous = (object)['questionattemptid' => 0];
        $quiz_attempt = (object)['attempt' => (object)['id' => -1]];
        foreach ($records as $info_rec){
            if ($info_rec->questionattemptid != $previous->questionattemptid){
                $previous = $info_rec;
                continue;
            }

            if ($quiz_attempt->attempt->id != $previous->attemptid){
                if (!empty($quiz_attempt->quba)){
                    \question_engine::save_questions_usage_by_activity($quiz_attempt->quba);
                }
                $quiz_attempt = theme_sql_quiz_create_attempt_handling_errors($previous->attemptid);
                $observer = $quiz_attempt->quba->get_observer();
            }

            $qa = $quiz_attempt->quba->get_question_attempt($previous->slot);
            foreach ($qa->get_step_iterator() as $step){
                if ($step->get_id() == $previous->stepid){
                    $observer->notify_step_deleted($step, $qa);
                    break;
                }
            }

            $previous = $info_rec;
        }

        if (!empty($quiz_attempt->quba)){
            \question_engine::save_questions_usage_by_activity($quiz_attempt->quba);
        }
        $trans->allow_commit();
    }
}