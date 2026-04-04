<?php

/**
 * PythonRunner renderer class.
 *
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  2023 Alexey Kazlovsky
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_pythonrunner\constants;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/sqlrunner/renderer.php');

/**
 * Subclass for generating the bits of output specific to pythonrunner questions.
 *
 * @copyright  Richard Lobb, University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pythonrunner_renderer extends qtype_sqlrunner_renderer {

    const EDITOR_TYPE = 'python';
    /**
     * Generate the specific feedback. This is feedback that varies according to
     * the response the student gave.
     * @param question_attempt $qa the question attempt to display.
     * @return string HTML fragment.
     */
    protected function specific_feedback(question_attempt $qa) {
        $toserialised = $qa->get_last_qt_var('_testoutcome');
        if (!$toserialised) { // Something broke?
            return '';
        }

        $q = $qa->get_question();
        $outcome = unserialize($toserialised);
        if ($outcome === false) {
            $outcome = new qtype_pythonrunner_testing_outcome(0, 0, false, null);
            $outcome->set_status(qtype_sqlrunner_testing_outcome::STATUS_UNSERIALIZE_FAILED);
        }
        $resultsclass = $this->results_class($outcome);

        $isoutputonly = $outcome->is_output_only();
        if ($isoutputonly) {
            $resultsclass .= ' outputonly';
        }
        $isprecheck = $outcome->is_precheck($qa);
        if ($isprecheck) {
            $resultsclass .= ' precheck';
        }

        $fb = html_writer::start_tag('div', array('class' => $resultsclass));
        if ($outcome->invalid() || $outcome->run_failed() || $outcome->has_syntax_error() || $outcome->combinator_error()) {
            $error = $qa->get_last_qt_var('_runnererror');
            $fb .= html_writer::tag('h5', sqlrunner_str('run_code_failed'));
            $fb .= html_writer::tag('p', s($error), array('class' => 'run_failed_error'));
        } else {
            // The run was successful (i.e didn't crash, but may be wrong answer). Display results.
            if ($isprecheck && $q->precheck == constants::PRECHECK_EMPTY && !$outcome->iscombinatorgrader()) {
                $fb .= $this->empty_precheck_status($outcome);
            } else {
                /** @var qtype_pythonrunner_question $q  */
                $expected_test = reset($q->testcases);
                if ($expected_test && $expected_test->expected){
                    $fb.= html_writer::tag('h5', sqlrunner_str('expected'), ['class' => 'user_expected_output']);
                    $fb .= $outcome->build_table($expected_test->expected);
                    $fb.= html_writer::tag('h5', sqlrunner_str('youroutput'), ['class' => 'user_output_data mt-5']);
                }
                $runneroutput = $qa->get_last_qt_var('_runneroutput');
                $fb .= $outcome->build_table($runneroutput);
            }
        }

        $fb .= html_writer::end_tag('div');
        return $fb;
    }
    /**
     *
     * @param qtype_sqlrunner_testing_outcome $outcome
     * @return string the CSS class for the given testing outcome
     */
    protected function results_class($outcome, $deprecated = null) {
        if ($outcome->all_correct()) {
            $resultsclass = "pythonrunner-test-results good";
        } else if ($outcome->mark_as_fraction() == 0) {
            $resultsclass = "pythonrunner-test-results bad";
        } else {
            $resultsclass = 'pythonrunner-test-results partial';
        }
        return $resultsclass;
    }

    public function render_video(question_attempt $qa, question_definition $question) {
        $context = [];
        $context['videourl'] = $question->videourl ?? null;
        return $this->output->render_from_template('qtype_sqlrunner/video_layout', $context);
    }
}
