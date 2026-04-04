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
 * Question behaviour for the old adaptive mode.
 *
 * @package    qbehaviour
 * @subpackage adaptive_adapted_for_sqlrunner
 * @copyright  2009 The Open University, 2022 The University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *  Need a special behaviour for sqlrunner questions (which are assumed to be
 *  run in some sort of adaptive mode), in order to avoid repeating
 *  the expensive test run whenever question::grade_response is called.
 *
 *  The solution adopted here is to override the process_submit method of
 *  the adaptive behaviour so that it calls the sqlrunner::grade_response_raw
 *  method, rather than sqlrunner::grade_response. The raw method takes the
 *  question_attempt_pending_step as a parameter rather than the response
 *  copied from that step. This allows the question to cache the test results
 *  within the step, which is stored in the database.
 *
 *  Also override adjusted_fraction and adaptive_mark_details_from_step to
 *  support the flexible CodeRunner penalty_regime.
 */

defined('MOODLE_INTERNAL') || die();
if(!defined('PRECHECK')){
    define('PRECHECK', true);
}

require_once($CFG->dirroot . '/question/behaviour/adaptive/behaviour.php');

class qbehaviour_adaptive_adapted_for_sqlrunner extends qbehaviour_adaptive {

    public bool $penaltiesenabled;

    protected $preferredbehaviour;

    public function __construct(question_attempt $qa, $preferredbehaviour) {
        parent::__construct($qa, $preferredbehaviour);
        $this->penaltiesenabled = $preferredbehaviour !== 'adaptivenopenalty';
        $this->preferredbehaviour = $preferredbehaviour;
    }

    public function can_finish_during_attempt() {
        return false;//$this->question->giveupallowed != constants::GIVEUP_NEVER;
    }

    public function is_compatible_question(question_definition $question) {
        // Restrict behaviour to programming questions.
        return $question instanceof qtype_sqlrunner_question
            || $question instanceof qtype_missingtype_question
            || $question instanceof qtype_data_modeling_question;
    }

    // Override parent method to allow for the added 'precheck' button.
    public function get_expected_data() {
        $vars = parent::get_expected_data();
        if (!$this->qa->get_state()->is_finished() && !empty($this->question->precheck)) {
            $vars['precheck'] = PARAM_BOOL;
        }
        if ($this->is_give_up_avaiable_now()) {
            $vars['finish'] = PARAM_BOOL;
        }
        if($this->question->get_type_name() == 'data_modeling'){
            $vars['table_code'] = PARAM_RAW;
        }
        // expect next parameter for 'Submit answer' button
        $vars['next'] = PARAM_BOOL;
        return $vars;
    }

    // Override parent method to allow for the added 'precheck' button.
    public function get_state_string($showcorrectness) {
        // We need to check, if one of student attempts had a completed state
        foreach ($this->qa->get_reverse_step_iterator() as $step){
            if($step->get_state() == question_state::$complete){
                $state = question_state::graded_state_for_fraction($step->get_fraction());
                return $state->default_string(true);
            }
        }

        if ($step->has_behaviour_var('precheck')) {
            return get_string('precheckresults', 'qbehaviour_adaptive_adapted_for_sqlrunner');
        }

        return parent::get_state_string($showcorrectness);
    }


    // Override default adaptive behaviour's save method to use the grand-parental
    // (question_behaviour_with_save) method instead of the parental
    // (question_behaviour_adaptive) if the question's hidecheck is true.
    // The code here replicates the process_save method of the question_behaviour
    // _with_save class, but calling the grandparental method directly seems unsafe.
    public function process_save(question_attempt_pending_step $pendingstep) {
        if (isset($this->question->hidecheck) && $this->question->hidecheck) {
            // Replicate the code from question_behaviour_with_save.
            if ($this->qa->get_state()->is_finished()) {
                return question_attempt::DISCARD;
            } else if (!$this->qa->get_state()->is_active()) {
                throw new coding_exception('Question is not active, cannot process_actions.');
            }

            if ($this->is_same_response($pendingstep)) {
                return question_attempt::DISCARD;
            }

            if ($this->is_complete_response($pendingstep)) {
                $pendingstep->set_state(question_state::$complete);
            } else {
                $pendingstep->set_state(question_state::$todo);
            }
            return question_attempt::KEEP;
        } else {
            // If check is visible, just use the standard adaptive behaviour.
            return parent::process_save($pendingstep);
        }
    }
    // Override parent method to allow for the added 'precheck' button.
    public function process_action(question_attempt_pending_step $pendingstep) {
        if ($pendingstep->has_behaviour_var('precheck')) {
            return $this->process_submit($pendingstep, PRECHECK);
        }

        if ($pendingstep->has_behaviour_var('next')) {
            // save slot for redirect to the next question
            global $USER;
            $USER->slot = $this->qa->get_slot();
            return $this->process_submit($pendingstep);
        }
        return parent::process_action($pendingstep);
    }


    public function summarise_action(question_attempt_step $step) {
        if ($step->has_behaviour_var('precheck')) {
            return $this->summarise_precheck($step);
        } else {
            return parent::summarise_action($step);
        }
    }

    public function process_submit(question_attempt_pending_step $pendingstep, $isprecheck = false) {
        $status = $this->process_save($pendingstep);
        $response = $pendingstep->get_qt_data();
        if (!$this->question->is_complete_response($response)) {
            $pendingstep->set_state(question_state::$invalid);
            if ($this->qa->get_state() != question_state::$invalid) {
                $status = question_attempt::KEEP;
            }
            return $status;
        }

        $prevstep = $this->qa->get_last_step_with_behaviour_var('_try');
        $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
        $prevbest = $pendingstep->get_fraction();

        $not_runner = $this->qa->get_question()->get_type_name() == 'data_modeling';
        if ($not_runner){
            $response = $pendingstep->get_qt_data();
            list($fraction, $state, $save_data) = $this->question->grade_response($response);
            if (!empty($save_data)) {
                foreach ($save_data as $name => $value) {
                    $pendingstep->set_qt_var($name, $value);
                }
            }
        } else {
            list($fraction, $state) = $this->grade_response($pendingstep, $isprecheck);
        }
        if ($fraction == 1){
            if ($this->question->get_type_name() == 'data_modeling'){
                \core\notification::add(get_string('question_submitted', 'qbehaviour_adaptive_adapted_for_sqlrunner'), \core\notification::WARNING);
            } else {
                \core\notification::add(get_string('question_completed', 'qbehaviour_adaptive_adapted_for_sqlrunner'), \core\notification::SUCCESS);
            }
        } else {
            \core\notification::add(get_string('question_not_completed', 'qbehaviour_adaptive_adapted_for_sqlrunner'), \core\notification::ERROR);
        }

        $pendingstep->set_behaviour_var('_try', $prevtries + 1);

        // First, handle a failed grading attempt (sandbox down?).
        // We know it's not exactly the same response twice in a row, so
        // safest to mark the step invalid, but keep it. This is mostly of
        // concern if the sandbox is down during a regrade.
        if ($state == question_state::$invalid) {
            $pendingstep->set_state(question_state::$invalid);
            $pendingstep->set_fraction($fraction);
            $this->_recalculate_steps();
            return question_attempt::KEEP;
        }

        $iscompleted = $state == question_state::$complete || $state == question_state::$gradedright;
        if ($iscompleted && !$isprecheck){
            $pendingstep->set_state(question_state::$complete);
        } else {
            $pendingstep->set_state(question_state::$todo);
        }

        // If runner
        if (!$not_runner){
            if ($isprecheck){
                // Leave mark unchanged. Increment try and numprechecks.
                $pendingstep->set_fraction($prevbest);
                $pendingstep->set_behaviour_var('_precheck', 1);
                $prevprechecks = $this->qa->get_last_behaviour_var('_numprechecks', 0);
                $pendingstep->set_behaviour_var('_numprechecks', $prevprechecks + 1);
                $prevraw = $this->qa->get_last_behaviour_var('_rawfraction', null);
            } else {
                if (is_null($prevbest)){
                    $prevbest = 0;
                }
                $pendingstep->set_fraction(max($prevbest, $this->adjusted_fraction($fraction, $prevtries)));
                $pendingstep->set_behaviour_var('_precheck', 0);
                $pendingstep->set_behaviour_var('_rawfraction', $fraction);
            }
        }
        $pendingstep->set_new_response_summary($this->question->summarise_response($response));
        $this->_recalculate_steps($state == question_state::$complete);
        return question_attempt::KEEP;
    }

    protected function _recalculate_steps($allow_correct = true){
        $observer = $this->_get_qa_observer();
        $seq = 0;
        $deleted_states = [
            question_state::$invalid,
            question_state::$gradedwrong,
        ];

        if (!$allow_correct){
            $deleted_states[] = question_state::$complete;
            $deleted_states[] = question_state::$gradedright;
        }

        /** @var question_attempt_step_read_only $step */
        foreach ($this->qa->get_step_iterator() as $step){
            if (($step->get_fraction() !== null && floor($step->get_fraction()) != 1) || in_array($step->get_state(), $deleted_states)){
                $observer->notify_step_deleted($step, $this->qa);
                continue;
            }
            $observer->notify_step_modified($step, $this->qa, $seq++);
        }
    }

    /**
     * @return question_usage_observer
     */
    protected function _get_qa_observer(){
        // get protected property observer from qa
        // this is one way to get it here to allow steps editing
        return \Closure::bind((fn () => $this->observer), $this->qa, $this->qa)();
    }

    // Grade the sqlrunner submission and cache the results in the pending step
    // for re-use.
    // Return a two-element array containing the mark (a fraction) and the state.
    protected function grade_response(question_attempt_pending_step $pendingstep,
            bool $isprecheck=false) {
        $response = $pendingstep->get_qt_data();
        $numprechecks = intval($this->qa->get_last_behaviour_var('_numprechecks', 0));
        $prevtries = intval($this->qa->get_last_behaviour_var('_try', 0));
        $response['numchecks'] = $prevtries - $numprechecks;
        $response['numprechecks'] = $numprechecks;
        $response['fraction'] = floatval($pendingstep->get_fraction());
        $response['preferredbehaviour'] = $this->preferredbehaviour;
        $graderstate = '';
        $testoutcomeserialised = $this->qa->get_last_qt_var('_testoutcome', '');
        if ($testoutcomeserialised !== '') {
            $testoutcome = unserialize($testoutcomeserialised);
            if (isset($testoutcome->graderstate)) {
                $graderstate = $testoutcome->graderstate;
            }
            $response['graderstate'] = $graderstate;
        }
        $gradedata = $this->question->grade_response($response, $isprecheck);
        list($fraction, $state) = $gradedata;
        if (count($gradedata) > 2) {
            foreach ($gradedata[2] as $name => $value) {
                $pendingstep->set_qt_var($name, $value);
            }
        }
        return array($fraction, $state);
    }


    // Override of adjusted_fraction to allow use of penaltyregime if defined
    // for this question. The penalty regime is a list of floating point
    // penalties, each a percent, to be applied in order on each submission.
    // If the last penalty is '...', expand the previous two entries as an
    // arithmetic progression.
    protected function adjusted_fraction($fraction, $prevtries) {
        $numprechecks = $this->qa->get_last_behaviour_var('_numprechecks', 0);
        $prevtries -= $numprechecks; // Deduct prechecks from tries.
        $prevtries = max($prevtries, 0); // Can't be negative
        if (!$this->penaltiesenabled || $prevtries == 0) {
            return $fraction;
        } else if (empty($this->question->penaltyregime)) { // Legacy questions may lack penalty regime.
            return parent::adjusted_fraction($fraction, $prevtries);
        } else {
            $penalties = explode(",", $this->question->penaltyregime);
            $n = count($penalties);
            if (trim($penalties[$n - 1]) === '...') {
                $delta = floatval($penalties[$n - 2]) - floatval($penalties[$n - 3]);
                $penalties[$n - 1] = min(100, $penalties[$n - 2] + $delta);
                while ($penalties[$n - 1] < 100) {
                    $penalties[] = min(100, $penalties[$n - 1] + $delta);
                    $n++;
                }
            }
            $i = min($n - 1, $prevtries - 1);
            $penalty = floatval($penalties[$i]) / 100.0;
            return $fraction * (1 - $penalty);
        }
    }

    /**
     * In the current state of the question attempt, should the 'Give up' button be shown now?
     *
     * @return bool true if it should.
     */
    public function is_give_up_avaiable_now(): bool {
        return false;
    }

    /**
     * Work out if it is possible for the student to make any further improvement in their score.
     *
     * @param question_attempt $qa the current attempt.
     * @return bool true if it is.
     */
    public function is_improvement_possible(): bool {
        $gradedstep = $this->get_graded_step();
        if (!$gradedstep) {
            return true; // They have not submitted anything yet. Doing better is certainly possible!
        }

        $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
        $fract = $this->adjusted_fraction(1.0, $prevtries);
        return $gradedstep->get_fraction() < $fract - qtype_sqlrunner_testing_outcome::TOLERANCE;
    }

    // Override usual adaptive mark details to handle penalty regime.
    // This is messy. Is there a better way?
    protected function adaptive_mark_details_from_step(
            question_attempt_step $gradedstep,
            question_state $state, $maxmark, $penalty) {
        if (!isset($this->question->penaltyregime) || $this->question->penaltyregime === '') {
            $details = parent::adaptive_mark_details_from_step($gradedstep, $state, $maxmark, $penalty);
        } else {
            $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
            $fract = $this->adjusted_fraction(1.0, $prevtries);
            $details = new qbehaviour_adaptive_mark_details($state);
            $details->maxmark    = $maxmark;
            $details->actualmark = $gradedstep->get_fraction() * $details->maxmark;
            $details->rawmark    = $gradedstep->get_behaviour_var('_rawfraction') * $details->maxmark;
            $details->totalpenalty   = 1.0 - $fract;
            $details->currentpenalty = $details->totalpenalty * $details->maxmark;
            $details->improvable = $this->is_state_improvable($gradedstep->get_state());
        }

        return $details;
    }


    public function process_finish(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
        $prevbest = $this->qa->get_fraction();
        if (is_null($prevbest)) {
            $prevbest = 0;
        }

        $laststep = $this->qa->get_last_step();
        $response = $laststep->get_qt_data();
        if (!$this->question->is_gradable_response($response)) {
            $state = question_state::$gaveup;
            $fraction = 0;
        } else {

            if ($laststep->has_behaviour_var('_try')) {
                // Last answer was graded, we want to regrade it. Otherwise the answer
                // has changed, and we are grading a new try.

                // There is a Moodle bug here, resulting in regrading of
                // already-graded questions.
                // See https://tracker.moodle.org/browse/MDL-42399.
                $prevtries -= 1;
            }

            /****** Changed bit #2 begins ***.
             * Cache extra data from grade response */
            $gradedata = $this->question->grade_response($response);
            list($fraction, $state) = $gradedata;
            if (count($gradedata) > 2) {
                foreach ($gradedata[2] as $name => $value) {
                    $pendingstep->set_qt_var($name, $value);
                }
            }
            $pendingstep->set_behaviour_var('_precheck', 0);
            /* *** end of changed bit #2 ***/

            $pendingstep->set_behaviour_var('_try', $prevtries + 1);
            $pendingstep->set_behaviour_var('_rawfraction', $fraction);
            $pendingstep->set_new_response_summary($this->question->summarise_response($response));
        }

        $pendingstep->set_state($state);
        $pendingstep->set_fraction(max($prevbest, $this->adjusted_fraction($fraction, $prevtries)));
        return question_attempt::KEEP;
    }

    /**
     * Summarise the student's action when they precheck a response.
     *
     * This gets shown in the response history table under each question when
     * a teacher reviews a students attempt.
     *
     * @param question_attempt_step $step the step to summarise.
     * @return string textual summary of that action.
     */
    public function summarise_precheck(question_attempt_step $step) {
        return get_string('precheckedresponse', 'qbehaviour_adaptive_adapted_for_sqlrunner',
                $this->question->summarise_response($step->get_qt_data()));
    }


    /**
     * Used by {@link start_based_on()} to get the data needed to start a new
     * attempt from the point this attempt has go to.
     * We need to override the base behaviour, because it returns the qt_data from
     * the highest-numbered question attempt step that has any qt data. For
     * normal questions, that is the answer but due to the override of
     * process_finish, it is the _outcome in our case.
     * @return string an array mapping 'answer' to the last stored answer if
     * there is one or an empty array otherwise.
     */
    protected function get_our_resume_data() {
        $answer = $this->qa->get_last_qt_var('answer');
        if ($answer) {
            return array('answer' => $answer);
        } else {
            return array();
        }
    }
}
