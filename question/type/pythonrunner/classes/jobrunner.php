<?php

/**
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  2016 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pythonrunner/questiontype.php');

use qtype_sqlrunner_testing_outcome as outcome;

// The qtype_pythonrunner_jobrunner class contains all code concerned with running a question
// in the sandbox and grading the result.
class qtype_pythonrunner_jobrunner extends qtype_sqlrunner_jobrunner {

    protected $_outcome_class = qtype_pythonrunner_testing_outcome::class;

    /**
     * Check the correctness of a student's code and possible extra attachments
     * as an answer to the given question and and a given set of test cases
     * (which may be empty or a subset of the question's set of testcases.
     * $isprecheck is true if this is a run triggered by the student clicking the Precheck button.
     * $answerlanguage will be the empty string except for multilanguage questions,
     * when it is the language selected in the language drop-down menu.
     * Returns a TestingOutcome object.
     *
     * @param qtype_pythonrunner_question $question
     * @param                             $code
     * @param                             $attachments
     * @param                             $testcases
     * @param                             $isprecheck
     * @param                             $answerlanguage
     *
     * @return qtype_sqlrunner_testing_outcome|null
     */
    public function run_tests($question, $code, $attachments, $testcases, $isprecheck, $answerlanguage){
        if (empty($question->prototype)){
            // Missing prototype. We can't run this question.
            $outcome = $this->outcome(0, 0, false, $this->grader);
            $message = sqlrunner_str('missingprototypewhenrunning', array('crtype' => $question->pythonrunnertype));
            $outcome->set_status(outcome::STATUS_MISSING_PROTOTYPE, $message);
            return $outcome;
        }
        $this->question = $question;
        $this->code = $code;
        $this->testcases = array_values($testcases);
        $this->isprecheck = $isprecheck;
        $this->grader = $question->get_grader();
        $this->sandbox = $question->get_sandbox();
        $this->files = [];
        $this->language = $question->get_language();
        $this->sandboxparams = $question->get_sandbox_params();

        $this->allruns = array();
        $this->templateparams = array(
            'STUDENT_ANSWER' => $code,
            'IS_PRECHECK' => (int) $isprecheck,
            'ANSWER_LANGUAGE' => $answerlanguage,
         );

        if ($question->get_is_combinator() && ($this->has_no_stdins() || $question->allow_multiple_stdins() || $this->grader->name() === 'TemplateGrader')) {
            $outcome = $this->run_combinator($isprecheck);
        } else {
            $outcome = null;
        }

        // If that failed for any reason (e.g. timeout or signal), or if the
        // template isn't a combinator, run the tests individually. Any compilation
        // errors or stderr output in individual tests bomb the whole test process,
        // but otherwise we should finish with a TestingOutcome object containing
        // a test result for each test case.
        if ($outcome == null) {
            $outcome = $this->run_tests_singly($isprecheck);
        }

        $this->sandbox->close();
        if ($question->get_show_source()) {
            $outcome->sourcecodelist = $this->allruns;
        }
        return $outcome;
    }


    // If the template is a combinator, try running all the tests in a single
    // go.
    //
    // Special template parameters are STUDENT_ANSWER, the raw submitted code,
    // IS_PRECHECK, which is true if this is a precheck run, TESTCASES,
    // a list of all the test cases and QUESTION, the original question object.
    // Return the testing outcome object if successful else null.
    protected function run_combinator($isprecheck) {
        $numtests = 1; // we have only 1 test case

        $this->templateparams['TESTCASES'] = $this->testcases;
        $maxmark = $this->maximum_possible_mark();
        $outcome = $this->outcome($maxmark, $numtests, $isprecheck, $this->grader);
        $question = $this->question;
        try {
            $testprog = $question->twig_expand($question->get_template(), $this->templateparams);
        } catch (Exception $e){
            $outcome->set_status(outcome::STATUS_SYNTAX_ERROR, sqlrunner_str('templateerror').': '.$e->getMessage());
            return $outcome;
        }

        $this->allruns[] = $testprog;
        $run = $this->sandbox->execute($testprog, $this->language, null, $this->files, $this->sandboxparams);

        // If it's a template grader, we pass the result to the
        // do_combinator_grading method. Otherwise we deal with syntax errors or
        // a successful result without accompanying stderr.
        // In all other cases (runtime error etc) we give up
        // on the combinator.


        if ($run->error !== qtype_sqlrunner_sandbox::OK){
            $outcome->set_status(outcome::STATUS_SANDBOX_ERROR, qtype_sqlrunner_sandbox::error_string($run));
        } elseif ($run->result === qtype_sqlrunner_sandbox::RESULT_SUCCESS) {
            $outputs = preg_split($this->question->get_test_splitter_re(), $run->output);
            if (count($outputs) === $numtests){
                $i = 0;
                foreach ($this->testcases as $testcase){
                    $outcome->add_test_result($this->grade($outputs[$i], $testcase));
                    $i++;
                }
            } else {  // Error: wrong number of tests after splitting.
                $error = pythonrunner_str('brokencombinator', array('numtests' => $numtests, 'numresults' => count($outputs)));
                $outcome->set_status(outcome::STATUS_BAD_COMBINATOR, $error);
            }
        } elseif ($run->result === qtype_sqlrunner_sandbox::RESULT_COMPILATION_ERROR) {
            $outcome->set_status(outcome::STATUS_SYNTAX_ERROR, $run->cmpinfo);
            // check if server works correctly, but something went wrong
        } elseif (qtype_sqlrunner_sandbox::RESULT_COMPILATION_ERROR < $run->result && $run->result < qtype_sqlrunner_sandbox::RESULT_SERVER_OVERLOAD) {
            $outcome->set_status(outcome::STATUS_SYNTAX_ERROR,
                qtype_sqlrunner_sandbox::result_string($run->result). "\n" . $run->stderr);
        } else {
            $outcome = null; // Something broke badly.
        }

        if ($outcome && isset($run->sandboxinfo)){
            $outcome->add_sandbox_info($run->sandboxinfo);
        }
        return $outcome;
    }
}
