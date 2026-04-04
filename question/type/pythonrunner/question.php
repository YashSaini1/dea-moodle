<?php
// This file is part of CodeRunner - http://coderunner.org.nz/
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
// along with CodeRunner.  If not, see <http://www.gnu.org/licenses/>.

/**
 * pythonrunner question definition classes.
 *
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  Richard Lobb, 2011, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/sqlrunner/question.php');
require_once($CFG->dirroot . '/question/type/pythonrunner/questiontype.php');

use qtype_pythonrunner\constants;

/**
 * Represents a 'CodeRunner' question.
 */
class qtype_pythonrunner_question extends qtype_sqlrunner_question {

    public $sql_queries;

    public function get_correct_answer() {
        // Return the sample answer, if supplied.
        if (!isset($this->answer)) {
            return null;
        } else {
            $answer = array('answer' => $this->answer);
            // For multilanguage questions we also need to specify the language.
            // Use the answer_language template parameter value if given, otherwise
            // run with the default.
            $params = $this->parameters;
            if (!empty($params->answer_language)) {
                $answer['language'] = $params->answer_language;
            } else if (!empty($this->acelang) && strpos($this->acelang, ',') !== false) {
                list($langs, $defaultlang) = qtype_pythonrunner_util::extract_languages($this->acelang);
                $default = empty($defaultlang) ? $langs[0] : $defaultlang;
                $answer['language'] = $default;
            }
            return $answer;
        }
    }

    /** Pulls out the step information in the response, added by the CodeRunner
    /*  custom behaviour, for use by the question author in issuing feedback.
     *
     * @param type $response The usual response array enhanced by the addition of
     * numchecks, numprechecks and fraction values relating to the current step.
     * @return stdClass object with the numchecks, numprechecks and fraction
     * attributes.
     */
    protected static function step_info($response) {
        $stepinfo = new stdClass();
        foreach (['numchecks', 'numprechecks', 'fraction', 'preferredbehaviour'] as $key) {
            $value = isset($response[$key]) ? $response[$key] : 0;
            $stepinfo->$key = $value;
        }
        $stepinfo->sqlrunnerversion = get_config('qtype_pythonrunner')->version;
        return $stepinfo;
    }


    /**
     * Evaluate a template parameter string using a given language on the Jobe
     * server. Return value should be the JSON template parameter string.
     *
     * @param string $templateparams The template parameters to evaluate.
     * @param int $seed The random number seed to use when evaluating.
     * @return string The output from the run.
     */
    protected function evaluate_template_params_on_jobe($templateparams, $lang, $seed) {
        $files = $this->get_files();
        $input = '';
        $runargs = array("seed=$seed");
        foreach (array('id', 'username', 'firstname', 'lastname', 'email') as $key) {
            $value = preg_replace("/[^A-Za-z0-9]/", '', $this->student->$key);
            $runargs[] = "$key=" . $value;
        }
        $sandboxparams = array("runargs" => $runargs);
        $sandbox = $this->get_sandbox();
        $run = $sandbox->execute($templateparams, $lang, $input, $files, $sandboxparams);
        if ($run->error === qtype_pythonrunner_sandbox::SERVER_OVERLOAD) {
            // Ugly. Probably a major test is running and we overloaded the server.
            $message = sqlrunner_str('overloadoninit');
            throw new qtype_sqlrunner_overload_exception($message);
        } else if ($run->error !== qtype_pythonrunner_sandbox::OK) {
            return qtype_pythonrunner_sandbox::error_string($run);
        } else if ($run->result != qtype_pythonrunner_sandbox::RESULT_SUCCESS) {
            return qtype_pythonrunner_sandbox::result_string($run->result) . "\n" . $run->cmpinfo . $run->output . $run->stderr;
        } else {
            return $run->output;
        }
    }

    // Render the given twig text using the given random number seed and
    // student variable. This version should be called only during question
    // initialisation when evaluating the template parameters.
    protected function twig_render_with_seed($text, $seed) {
        mt_srand($seed);
        return qtype_pythonrunner_twig::render($text, $this->student);
    }

    // Get the default ui parameters for the ui plugin and merge in
    // both the prototypes and this questions parameters.
    // In order to support the legacy method of including ui parameters
    // within the template parameters, we need to filter out only the
    // valid ui parameters, so need to load the uiplugin json file to find
    // which ones are supported.
    // The order of evaluation (later values overriding earlier ones) is:
    // built-in defaults; plugin's json defaults; modern prototype ui params;
    // legacy prototype template params; legacy question template params;
    // modern question ui params.
    // Return the the merged parameters as an associative array.
    protected function evaluate_merged_ui_parameters() {
        $uiplugin = $this->uiplugin === null ? 'ace' : strtolower($this->uiplugin);
        $uiparams = new qtype_sqlrunner_ui_parameters($uiplugin);
        if (isset($this->prototype->uiparameters)) { // Ensure prototype not missing.
            $uiparams->merge_json($this->prototype->uiparameters);
        }
        $uiparams->merge_json($this->templateparamsjson, true); // Legacy support.
        $uiparams->merge_json($this->uiparameters);
        return $uiparams->updated_params();
    }

    /**
     * Grade the given student's response.
     * This implementation assumes a modified behaviour that will accept a
     * third array element in its response, containing data to be cached and
     * served up again in the response on subsequent calls.
     * @param array $response the qt_data for the current pending step. The
     * main relevant keys are '_testoutcome', which is a cached copy of the
     * grading outcome if this response has already been graded and 'answer'
     * (the student's answer) otherwise. Also present are 'numchecks',
     * 'numprechecks' and 'fraction' which relate to the current (pending) step and
     * the history of prior submissions.
     * @param bool $isprecheck true iff this grading is occurring because the
     * student clicked the precheck button
     * @param int $is_edit
     *
     * @return 3-element array of the mark (0 - 1), the question_state (
     * gradedright, gradedwrong, gradedpartial, invalid) and the full
     * qtype_pythonrunner_testing_outcome object to be cached. The invalid
     * state is used when a sandbox error occurs.
     * @throws coding_exception
     */
    public function grade_response(array $response, bool $isprecheck = false, bool $is_edit = false){
        if ($isprecheck && empty($this->precheck)){
            throw new coding_exception("Unexpected precheck");
        }
        $testoutcomeserial = null;
        $language = empty($response['language']) ? '' : $response['language'];
        $gradingreqd = true;
        if (!empty($response['_testoutcome'])){
            $testoutcomeserial = $response['_testoutcome'];
            $testoutcome = unserialize($testoutcomeserial);
            if ($testoutcome instanceof qtype_pythonrunner_testing_outcome  // Ignore legacy-format outcomes.
                && $testoutcome->isprecheck == $isprecheck){
                $gradingreqd = false;  // Already graded and with same precheck state.
            }
        }
        if ($gradingreqd){
            // We haven't already graded this submission or we graded it with
            // a different precheck setting. Get the code and the attachments
            // from the response. The attachments is an array with keys being
            // filenames and values being file contents.
            $code = $response['answer'];
            $attachments = $this->get_attached_files($response);
            $testcases = $this->filter_testcases($isprecheck, !empty($this->precheck));
            $runner = new qtype_pythonrunner_jobrunner();
            $this->stepinfo = static::step_info($response);
            if (isset($response['graderstate'])){
                $this->stepinfo->graderstate = $response['graderstate'];
            } else {
                $this->stepinfo->graderstate = '';
            }
            $testoutcome = $runner->run_tests($this, $code, $attachments, $testcases, $isprecheck, $language);
        }

        return $this->_get_grade_response_data($testoutcome, $testoutcomeserial, $is_edit);
    }

    // Return a stdObject pseudo-clone of this question with only the fields
    // documented in the README.md, for use in Twig expansion.
    // HACK ALERT - the field uiparameters exported to the Twig context is
    // actually the mergeduiparameters field, just as the parameters field
    // is the merged template parameters. [Where merging refers to the combining
    // of the prototype and the question].
    protected function sanitised_clone_of_this() {
        $clone = new stdClass();
        $fieldsrequired = array('id', 'name', 'questiontext', 'generalfeedback',
            'generalfeedbackformat', 'testcases', 'sql_queries',
            'answer', 'answerpreload', 'language', 'globalextra', 'useace', 'sandbox',
            'grader', 'cputimelimitsecs', 'memlimitmb', 'sandboxparams',
            'parameters', 'resultcolumns', 'precheck',
            'hidecheck', 'penaltyregime', 'iscombinatortemplate',
            'allowmultiplestdins', 'acelang', 'uiplugin', 'attachments',
            'attachmentsrequired', 'displayfeedback', 'stepinfo');
        foreach ($fieldsrequired as $field) {
            if (isset($this->$field)) {
                $clone->$field = $this->$field;
            } else {
                $clone->$field = null;
            }
        }
        if (isset($this->mergeduiparameters)) { // Only available at execution time.
            $clone->uiparameters = $this->mergeduiparameters;
        }
        $clone->questionid = $this->id; // Legacy support.
        return $clone;
    }

    /**
     * Evaluate the template parameter field for this question alone (i.e.
     * not including its prototype).
     *
     * @param int $seed the random number seed for this instance of the question
     * @param question_attempt_step $step the current attempt step
     * @param string $qtvar the base name of a qt_variable in which to record
     * the md5 hash of the current template parameters (with suffix '_md5') and the evaluated
     * json (with suffix '_json').
     * @return string The Json template parameters.
     */
    public function template_params_json($seed = 0, $step = null, $qtvar = ''){
        // no cache any parameters
        if (!empty($this->templateparams)){
            return $this->templateparams;
        }
        return parent::template_params_json($seed, $step, $qtvar);
    }

    /**
     * Return Twig-expanded version of the given text.
     * Twig environment includes the question itself (this) and, if template
     * parameters are to be hoisted, the (key, value) pairs in $this->parameters.
     * @param string $text Text to be twig expanded.
     */
    public function twig_expand($text, $context=array()) {
        if (empty(trim($text))) {
            return $text;
        }

        $context['QUESTION'] = $this->sanitised_clone_of_this();
        if (!empty($this->hoisttemplateparams)) {
            foreach ($this->parameters as $key => $value) {
                $context[$key] = $value;
            }
        }

        if (!empty($this->sql_queries)){
            foreach ($this->sql_queries as $sqlobject){
                $context[$sqlobject->fieldname] = $sqlobject->data;
            }
        }

        return trim(qtype_pythonrunner_twig::render($text, null, $context));
    }

    // Return an instance of the sandbox to be used to run code for this question.
    public function get_sandbox($additional_params = []) {
        $sandbox = $this->sandbox ?? null; // Get the specified sandbox (if question has one).
        if ($sandbox === null) {   // No sandbox specified. Use best we can find.
            $sandboxinstance = qtype_pythonrunner_sandbox::get_best_sandbox($this->language);
            if ($sandboxinstance === null) {
                throw new qtype_pythonrunner_exception("Language {$this->language} is not available on this system");
            }
        } else {
            $sandboxinstance = qtype_pythonrunner_sandbox::get_instance($sandbox);
            if ($sandboxinstance === null) {
                throw new qtype_pythonrunner_exception("Question is configured to use a non-existent or disabled sandbox ($sandbox)");
            }
        }
        $sandboxinstance->add_additional_params($additional_params);
        $this->sandbox = $sandbox;
        return $sandboxinstance;
    }

    // Get an instance of the grader to be used to grade this question.
    public function get_grader() {
        $grader = $this->grader == null ? constants::DEFAULT_GRADER : $this->grader;
        $graders = qtype_pythonrunner_grader::available_graders();
        $graderclass = $graders[$grader];

        return new $graderclass();
    }

    /**
     * Load the prototype for this question and store in $this->prototype
     */
    public function get_prototype() {
        if (isset($this->prototype)) {
            return;  // Nothing to do.
        }
        if ($this->prototypetype == 0) {
            $context = qtype_pythonrunner::question_context($this);
            $this->prototype = qtype_pythonrunner::get_prototype($this->pythonrunnertype, $context);
        } else {
            $this->prototype = null;
        }
    }

    /**
     *  Return an associative array mapping filename to file contents
     *  for all the support files for the given question.
     *  The sample answer files are not included in the return value.
     */
    protected static function get_support_files($question) {
        global $DB, $USER;

        // If not given in the question object get the contextid from the database.
        if (isset($question->contextid)) {
            $contextid = $question->contextid;
        } else {
            $context = qtype_pythonrunner::question_context($question);
            $contextid = $context->id;
        }

        $fs = get_file_storage();
        $filemap = array();

        if (isset($question->supportfilemanagerdraftid)) {
            // If we're just validating a question, get files from user draft area.
            $draftid = $question->supportfilemanagerdraftid;
            $context = context_user::instance($USER->id);
            $files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, '', false);
        } else {
            // Otherwise, get the stored support files for this question (not
            // the sample answer files).
            $files = $fs->get_area_files($contextid, 'qtype_pythonrunner', 'datafile', $question->id);
        }

        foreach ($files as $f) {
            $name = $f->get_filename();
            if ($name !== '.') {
                $filemap[$f->get_filename()] = $f->get_content();
            }
        }
        return $filemap;
    }
}
