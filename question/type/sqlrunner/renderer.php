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
 * CodeRunner renderer class.
 *
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  2012 Richard Lobb, The University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use qtype_sqlrunner\constants;

require_once($CFG->dirroot . '/question/type/sqlrunner/lib.php');

/**
 * Subclass for generating the bits of output specific to sqlrunner questions.
 *
 * @copyright  Richard Lobb, University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class qtype_sqlrunner_renderer extends qtype_renderer {

    const EDITOR_TYPE = 'mysql';

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the question text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options, $expected = null) {
        global $USER;

        $question = $qa->get_question();
        $qid = $question->id;
        if (empty($USER->sqlrunnerquestionids)) {
            $USER->sqlrunnerquestionids = array($qid);  // Record in case of AJAX request.
        } else {
            array_push($USER->sqlrunnerquestionids, $qid); // Array of active qids.
        }

        $context = [];
        $responsefieldname = $qa->get_qt_field_name('answer');
        $responsefieldid = 'id_' . $responsefieldname;

        $currentlanguage = static::EDITOR_TYPE; // hard-code language type
        //$currentlanguage = $question->language;

        $preload = isset($question->answerpreload) ? $question->answerpreload : '';
        if ($preload) {  // Add a reset button if preloaded text is non-empty.
            $context['reset_button'] = $this->reset_button($qa, $responsefieldid, $preload);
        }

        if (empty($expected)){
            $currentanswer = trim($qa->get_last_qt_var('answer'));

            if ($currentanswer === null || $currentanswer === ''){
                $currentanswer = $preload;
            } else {
                // Horrible horrible hack for horrible horrible browser feature
                // of ignoring a leading newline in a textarea. So we inject an
                // extra one to ensure that if the answer begins with a newline it
                // is preserved.
//            $currentanswer = "\n" . $currentanswer;
            }
        } else {
            $currentanswer = $expected;
        }

        $rows = $question->answerboxlines ?? constants::DEFAULT_NUM_ROWS;
        $taattributes = $this->answerbox_attributes($responsefieldname, $rows, $question, $currentlanguage, $options->readonly);

        $context['currentanswer'] =  $currentanswer;
        $context['taattributes'] = html_writer::attributes($taattributes);

        // Initialise any JavaScript UI. Default is Ace unless uiplugin is explicitly
        // set and is neither the empty string nor the value 'none'.
        // Thanks to Ulrich Dangel for the original implementation of the Ace code editor.
        $uiplugin = $question->uiplugin === null ? 'ace' : strtolower($question->uiplugin);
        if ($uiplugin !== '' && $uiplugin !== 'none') {
            qtype_sqlrunner_util::load_uiplugin_js($question, $responsefieldid);
            if (!empty($question->acelang) && strpos($question->acelang, ',') != false) {
                // For multilanguage questions, add javascript to switch the
                // Ace language when the user changes the selected language.
                $this->page->requires->js_call_amd('qtype_sqlrunner/multilanguagequestion', 'initLangSelector', array($responsefieldid));
            }
        } else {
            $this->page->requires->js_call_amd('qtype_sqlrunner/textareas', 'initQuestionTA', array($responsefieldid));
        }

        if (!$options->readonly){
            $this->output->page->requires->js_call_amd('qtype_sqlrunner/submit_answer_preloader', 'init');
        }
        return $this->output->render_from_template('qtype_sqlrunner/editor_layout', $context);
    }

    /**
     * @param question_attempt         $qa
     * @param question_display_options $options
     *
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_question_info(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $qid = $question->id;
        $context = [];
        $divid = "qtype_sqlrunner_problemspec$qid";
        $params = $question->parameters;

        if (isset($question->initialisationerrormessage) && $question->initialisationerrormessage) {
            $context['initialisationerrormessage'] = "<div class='initialisationerror'>{$question->initialisationerrormessage}</div>";
        }

        $context['questiontext'] = $question->format_questiontext($qa);
        if (isset($params->programming_contest_problem) && $params->programming_contest_problem) {
            // Special case hack for programming contest problems.
            $context['programming_contest_problem'] = "<div id='$divid'></div>";
            $probspecfilename = isset($params->problem_spec_filename) ? $params->problem_spec_filename : '';
            $this->page->requires->js_call_amd('qtype_sqlrunner/ajaxquestionloader',
                'loadQuestionText', array($qid, $divid, $probspecfilename));
        }
        $examples = $question->example_testcases();
        if (count($examples) > 0) {
            $resultcolumns = $question->result_columns();
            $context['exapmle_string'] = sqlrunner_str('forexample');
            $context['exapmles_data'] = $this->format_examples($examples, $resultcolumns);
        }

        $responsefieldname = $qa->get_qt_field_name('answer');
        $context['responsefieldid'] = 'id_' . $responsefieldname;

        $behaviour = $qa->get_behaviour(true);
        if ($behaviour->penaltiesenabled && $qa->has_marks()) {
            $context['penaltystring'] = $this->penalty_regime_string($qa);
        }
        return $this->output->render_from_template('qtype_sqlrunner/question_info_layout', $context);
    }

    /**
     * Override the base class method to allow CodeRunner questions to force
     * specific feedback to be displayed or hidden regardless of the quiz
     * review options.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function feedback(question_attempt $qa, question_display_options $options) {
        $optionsclone = clone($options);
        /** @var qtype_sqlrunner_question $q */
        $q = $qa->get_question();
        $feedbackdisplay = $q->display_feedback();

        // Update options for displaying specific feedback.
        if ($feedbackdisplay !== constants::FEEDBACK_USE_QUIZ && !empty($qa->get_last_qt_var('_testoutcome'))) {
            if ($feedbackdisplay === CONSTANTS::FEEDBACK_SHOW) {
                $optionsclone->feedback = 1;
            } else if ($feedbackdisplay === CONSTANTS::FEEDBACK_HIDE) {
                $optionsclone->feedback = 0;
            } else {
                throw new coding_exception("Invalid value of feedbackdisplay: $feedbackdisplay");
            }
        }

        // Update options for displaying general feedback.
        if ($feedbackdisplay === CONSTANTS::FEEDBACK_SHOW) {
            if ($qa->get_state()->is_finished() && $q->giveupallowed) {
                $optionsclone->generalfeedback = 1;
            }
        }

        return parent::feedback($qa, $optionsclone);
    }

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
            $outcome = new qtype_sqlrunner_testing_outcome(0, 0, false, null);
            $outcome->set_status(qtype_sqlrunner_testing_outcome::STATUS_UNSERIALIZE_FAILED);
        }
        $resultsclass = $this->results_class($outcome, $q->allornothing);

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
                /** @var qtype_sqlrunner_question $q  */
                $expected_test = reset($q->testcases);
                if ($expected_test && $expected_test->expected){
                    $fb.= html_writer::tag('h5', sqlrunner_str('expected'), ['class' => 'user_expected_output']);
                    $fb .= render_submission_table($expected_test->expected);
                    $fb.= html_writer::tag('h5', sqlrunner_str('youroutput'), ['class' => 'user_output_data mt-5']);
                }
                $sqloutput = $qa->get_last_qt_var('_runneroutput');
                $fb .= render_submission_table($sqloutput);
            }
        }

        $fb .= html_writer::end_tag('div');
        return $fb;
    }

    /**
     * Return html to display the status of an empty precheck run.
     * @param qtype_sqlrunner_testing_outcome $outcome the results from the test
     * Must be a standard testing outcome, not a combinator grader outcome.
     * @return html string describing the outcome
     */
    protected function empty_precheck_status($outcome) {
        $output = $outcome->get_raw_output();
        if (!empty($output)) {
            $fb = html_writer::tag('p', sqlrunner_str('bademptyprecheck'));
            $fb .= html_writer::tag('pre', qtype_sqlrunner_util::format_cell($output),
                    array('class' => 'bad_empty_precheck'));
        } else {
            $fb = html_writer::tag('p', sqlrunner_str('goodemptyprecheck'),
                    array('class' => 'good_empty_precheck'));
        }
        return $fb;

    }

    // Generate the main feedback, consisting of (in order) any prologuehtml,
    // a table of results and any epiloguehtml. Finally append a warning if
    // question is being tested using the University of Canterbury's testing
    // Jobe server.
    protected function build_results_table($outcome, qtype_sqlrunner_question $question) {
        global $CFG;
        $fb = $outcome->get_prologue();
        $testresults = $outcome->get_test_results($question);
        if (is_array($testresults) && count($testresults) > 1) {
            $table = new html_table();
            $table->attributes['class'] = 'sqlrunner-test-results';
            $headers = $testresults[0];
            foreach ($headers as $header) {
                if (strtolower($header) != 'ishidden') {
                    $table->head[] = strtolower($header) === 'iscorrect' ? '' : $header;
                }
            }

            $rowclasses = array();
            $tablerows = array();

            for ($i = 1; $i < count($testresults); $i++) {
                $cells = $testresults[$i];
                $rowclass = $i % 2 == 0 ? 'r0' : 'r1';
                $tablerow = array();
                $j = 0;
                foreach ($cells as $cell) {
                    if (strtolower($headers[$j]) === 'iscorrect') {
                        $markfrac = (float) $cell;
                        $tablerow[] = $this->feedback_image($markfrac);
                    } else if (strtolower($headers[$j]) === 'ishidden') { // Control column.
                        if ($cell) { // Anything other than zero or false means hidden.
                            $rowclass .= ' hidden-test';
                        }
                    } else if ($cell instanceof qtype_sqlrunner_html_wrapper) {
                        $tablerow[] = $cell->value();  // It's already HTML.
                    } else {
                        $tablerow[] = qtype_sqlrunner_util::format_cell($cell);
                    }
                    $j++;
                }
                $tablerows[] = $tablerow;
                $rowclasses[] = $rowclass;
            }
            $table->data = $tablerows;
            $table->rowclasses = $rowclasses;
            $fb .= html_writer::table($table);

        }
        $fb .= empty($outcome->epiloguehtml) ? '' : $outcome->epiloguehtml;

        // Issue a bright yellow warning if using jobe2, except when running behat.
        $sandboxinfo = $outcome->get_sandbox_info();
        if (isset($sandboxinfo['jobeserver'])) {
            $jobeserver = $sandboxinfo['jobeserver'];
            $apikey = $sandboxinfo['jobeapikey'];
            if ($jobeserver == constants::JOBE_HOST_DEFAULT && $CFG->prefix !== 'b_') {
                if ($apikey == constants::JOBE_HOST_DEFAULT_API_KEY) {
                    $fb .= sqlrunner_str('jobe_warning_html');
                } else {
                    $fb .= sqlrunner_str('jobe_canterbury_html');
                }
            }
        }

        return $fb;
    }


    // Compute the HTML feedback summary for this test outcome.
    // Should not be called if there were any syntax or sandbox errors.
    protected function build_feedback_summary(question_attempt $qa, qtype_sqlrunner_testing_outcome $outcome) {
        if ($outcome->iscombinatorgrader()) {
            // Simplified special case.
            return $this->build_combinator_grader_feedback_summary($qa, $outcome);
        }
        $question = $qa->get_question();
        $isprecheck = $outcome->is_precheck($qa);
        $lines = array();  // List of lines of output.

        $onlyhiddenfailed = false;
        if ($outcome->was_aborted()) {
            $lines[] = sqlrunner_str('aborted');
        } else {
            $hiddenerrors = $outcome->count_hidden_errors();
            if ($outcome->get_error_count() > 0) {
                if ($outcome->get_error_count() == $hiddenerrors) {
                    $onlyhiddenfailed = true;
                    $lines[] = sqlrunner_str('failedhidden');
                } else if ($hiddenerrors > 0) {
                    $lines[] = sqlrunner_str('morehidden');
                }
            }
        }

        if ($outcome->all_correct()) {
            if (!$isprecheck) {
                $lines[] = sqlrunner_str('allok') .
                        "&nbsp;" . $this->feedback_image(1.0);
            }
        } else {
            if ($question->allornothing && !$isprecheck) {
                $lines[] = sqlrunner_str('noerrorsallowed');
            }

            // Provide a show differences button if answer wrong and equality grader used.
            if ((empty($question->grader) ||
                 $question->grader == 'EqualityGrader' ||
                 $question->grader == 'NearEqualityGrader') &&
                    !$onlyhiddenfailed) {
                $lines[] = $this->diff_button($qa);
            }
        }

        return qtype_sqlrunner_util::make_html_para($lines);
    }


    // A special case of the above method for use with combinator template graders
    // only.
    protected function build_combinator_grader_feedback_summary($qa, qtype_sqlrunner_combinator_grader_outcome $outcome) {
        $isprecheck = $outcome->is_precheck($qa);
        $lines = array();  // List of lines of output.

        if ($outcome->all_correct() && !$isprecheck) {
            $lines[] = sqlrunner_str('allok') .
                    "&nbsp;" . $this->feedback_image(1.0);
        }

        if ($outcome->show_differences()) {
             $lines[] = $this->diff_button($qa);
        }

        return qtype_sqlrunner_util::make_html_para($lines);
    }


    // Build and return an HTML div section containing a list of template
    // outputs used as source code (which are recorded in the given $outcome).
    protected function make_source_code_div($outcome) {
        $html = '';
        $sourcecodelist = $outcome->get_sourcecode_list();
        if ($sourcecodelist && count($sourcecodelist) > 0) {
            $heading = sqlrunner_str('sourcecodeallruns');
            $html = html_writer::start_tag('div', array('class' => 'debugging'));
            $html .= html_writer::tag('h3', $heading);
            $i = 1;
            foreach ($sourcecodelist as $run) {
                $html .= html_writer::tag('h4', "Run $i");
                $i++;
                $html .= html_writer::tag('pre', s($run));
                $html .= html_writer::tag('hr', '');
            }
            $html .= html_writer::end_tag('div');
        }
        return $html;
    }


    /**
     * Return a string describing the penalties in place for this question.
     * @param type $qa
     * @return type
     */
    protected function penalty_regime_string(question_attempt $qa) {
        $question = $qa->get_question();
        if (empty($question->penaltyregime) && $question->penaltyregime !== '0') {
            if (intval(100 * $question->penalty) == 100 * $question->penalty) {
                $decdigits = 0;
            } else {
                $decdigits = 1;
            }
            $penaltypercent = number_format($question->penalty * 100, $decdigits);
            $penaltypercent2 = number_format($question->penalty * 200, $decdigits);
            $penalties = $penaltypercent . ', ' . $penaltypercent2 . ', ...';
        } else {
            $penalties = $question->penaltyregime;
        }
        return sqlrunner_str('penaltyregime', $penalties);
    }


    /**
     * Return the HTML to display the sample answer, if given.
     * @param question_attempt $qa
     * @return string The html for displaying the sample answer.
     */
    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $answer = $question->answer;
        if (!$answer) {
            return '';
        } else {
            $answer = "\n" . $answer; // Hack to ensure leading new line not lost.
        }
        $fieldname = $qa->get_qt_field_name('sampleanswer');
        $currentlanguage = $question->acelang ? $question->acelang : $question->language;
        if (strpos($question->acelang, ',') !== false) {
            // Case of a multilanguage question sample answer. Find the language,
            // which is specified by the template parameter answer_language if
            // given, or the default (starred) language in the language list
            // if given or the first language listed, whichever comes first.
            list($languages, $default) = qtype_sqlrunner_util::extract_languages($question->acelang);
            $params = $question->parameters;
            if (property_exists($params, 'answer_language')) {
                $currentlanguage = $params->answer_language;
            } else if (!empty($default)) {
                $currentlanguage = $default;
            } else {
                $currentlanguage = $languages[0];
            }
        }

        $uclang = ucwords($currentlanguage);
        $heading = sqlrunner_str('asolutionis');
        $heading = substr($heading, 0, strlen($heading) - 1) . ' (' . $uclang . '):';
        $html = html_writer::start_tag('div', array('class' => 'sample code'));
        $html .= html_writer::tag('h4', $heading);
        $answerboxlines = isset($question->answerboxlines) ? $question->answerboxlines : constants::DEFAULT_NUM_ROWS;
        if ($question->uiplugin == 'ace') {
            $rows = min($answerboxlines, substr_count($answer, "\n"));
        } else {
            $rows = $answerboxlines;
        }
        $taattributes = $this->answerbox_attributes($fieldname, $rows, $question,
                $currentlanguage, true);

        $html .= html_writer::tag('textarea', s($answer), $taattributes);
        $html .= html_writer::end_tag('div');
        $uiplugin = $question->uiplugin === null ? 'ace' : strtolower($question->uiplugin);
        $fieldid = 'id_' . $fieldname;
        if ($uiplugin !== '' && $uiplugin !== 'none') {
            qtype_sqlrunner_util::load_uiplugin_js($question, $fieldid);
        } else {
            $this->page->requires->js_call_amd('qtype_sqlrunner/textareas', 'initQuestionTA', array($fieldid));
        }
        return $html;
    }


    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $output = array();

        foreach ($files as $file) {
            $output[] = html_writer::tag('p', html_writer::link($qa->get_response_file_url($file),
                    $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

    /**
     * Displays the input control for when the student is allowed to upload files.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_input(question_attempt $qa, $numallowed,
            question_display_options $options) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $question = $qa->get_question();
        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $numallowed;
        $pickeroptions->maxbytes = intval($question->maxfilesize);
        $pickeroptions->context = $options->context;
        $pickeroptions->return_types = FILE_INTERNAL | FILE_CONTROLLED_LINK;
        $pickeroptions->accepted_types = '*';  // Accept anything - names checked on upload.
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
                'attachments', $options->context->id);

        $fm = new form_filemanager($pickeroptions);
        $filesrenderer = $this->page->get_renderer('core', 'files');

        $text = '';
        if (!empty($question->filenamesexplain)) {
                $text = $question->filenamesexplain;
        } else if (!empty($question->filenamesregex)) {
            $text = html_writer::tag('p', sqlrunner_str('allowedfilenamesregex')
                    . ': ' . $question->filenamesregex);
        }

        // In order to prevent a spurious warning message when checking or saving
        // the question after modifying the uploaded files, we need to explicitly
        // initialise the form change checker, to ensure the onsubmit action for
        // the form calls the set_form_submitted function in the module.
        // This is only needed during Preview as it's apparently done anyway
        // in normal quiz display mode, but we do it here regardless.
        $this->page->requires->yui_module('moodle-core-formchangechecker',
                'M.core_formchangechecker.init',
                array(array('formid' => 'responseform'))
        );
        $this->page->requires->string_for_js('changesmadereallygoaway', 'moodle');
        return $filesrenderer->render($fm). html_writer::empty_tag(
                'input', array('type' => 'hidden', 'name' => $qa->get_qt_field_name('attachments'),
                'value' => $pickeroptions->itemid)) . $text;
    }


    /**
     *
     * @param array $examples The array of testcases tagged "use as example"
     * @param array $resultcolumns the array of 2-element arrays specifying what
     * columns should appear in the result table, and their formats.
     * @return string An HTML table element displaying all the testcases.
     */
    private function format_examples($examples, $resultcolumns) {
        $table = new html_table();
        $table->attributes['class'] = 'sqlrunnerexamples';

        // Record counts of non-empty cells in each column so empty columns are suppressed.
        // But always show the 'expected' column (renaming it to 'result').
        list($numtests, $numstds, $numextras) = $this->count_bits($examples);
        $counts = array('testcode' => $numtests, 'stdin' => $numstds, 'extra' => $numextras, 'expected' => 1);

        $table->head = array();
        $table->data = array();
        $table->rowclasses = array();
        $i = 0;
        foreach ($examples as $example) {
            $row = array();
            foreach (array('testcode', 'stdin', 'extra', 'expected') as $col) {
                if ($counts[$col] && $this->show_column($col, $resultcolumns)) {
                    if ($i == 0) {
                        $table->head[] = $this->column_header($col, $resultcolumns);
                    }
                    if ($this->column_format($col, $resultcolumns) == '%h') {
                        $row[] = $example->$col; // For html (%h) column, use raw value.
                    } else { // Otherwise wrap value in <pre> element.
                        $row[] = qtype_sqlrunner_util::format_cell($example->$col);
                    }
                }
            }
            $table->data[] = $row;
            $table->rowclasses[] = $i % 2 == 0 ? 'r0' : 'r1';
            $i++;
        }
        return html_writer::table($table);
    }


    // Return a count of the number of non-empty stdins, tests and extras
    // in the given list of test result objects.
    private function count_bits($tests) {
        $numstds = 0;
        $numtests = 0;
        $numextras = 0;
        foreach ($tests as $test) {
            if (trim($test->stdin) !== '') {
                $numstds++;
            }
            if (trim($test->testcode) !== '') {
                $numtests++;
            }
            if (trim($test->extra) !== '') {
                $numextras++;
            }
        }
        return array($numtests, $numstds, $numextras);
    }

    // True iff the given testcase field is specified by the given question
    // resultcolumns field to be displayed.
    private function show_column($field, $resultcolumns) {
        foreach ($resultcolumns as $columnspecifier) {
            if ($columnspecifier[1] === $field) {
                return true;
            }
        }
        return false;
    }


    // Return the column header to be used for the given testcase field,
    // as specified by the question's resultcolumns field.
    // But HACK ALERT - the 'expected' column is renamed to 'Result' in
    // the example table.
    private function column_header($field, $resultcolumns) {
        if ($field === 'expected') {
            return sqlrunner_str('resultcolumnheader');
        }
        foreach ($resultcolumns as $columnspecifier) {
            if ($columnspecifier[1] === $field) {
                return $columnspecifier[0];
            }
        }
        return 'ERROR';
    }

    // Return the format to be used for the given field. If no specific
    // format given, return %s.
    private function column_format($field, $resultcolumns) {
        foreach ($resultcolumns as $columnspecifier) {
            if (count($columnspecifier) > 2 && $columnspecifier[1] === $field) {
                return trim($columnspecifier[2]);
            }
        }
        return '%s';
    }

    // Return the text area attributes for an answer box.
    protected function answerbox_attributes($fieldname, $rows, $question, $currentlanguage, $readonly=false) {
        if ($question->mergeduiparameters) {
            $uiparamsjson = json_encode($question->mergeduiparameters);
        } else {
            $uiparamsjson = '{}';
        }
        $attributes = array(
                'class' => 'sqlrunner-answer edit_code',
                'name' => $fieldname,
                'id' => 'id_' . $fieldname,
                'spellcheck' => 'false',
                'rows' => $rows,
                'data-params' => $uiparamsjson,
                'data-globalextra' => '',//$question->globalextra,
                'data-prototypeextra' => '',//$question->prototypeextra,
                'data-lang' => ucwords($currentlanguage),
                'data-test0' => $question->testcases ? $question->testcases[0]->testcode : ''
        );

        if ($readonly) {
            $attributes['readonly'] = '';
        }
        return $attributes;
    }


    // Return the HTML for a language dropdown list for the given question attempt.
    private function language_dropdown($qa) {
        $question = $qa->get_question();
        list($languages, $default) = qtype_sqlrunner_util::extract_languages($question->acelang);
        $currentlanguage = $qa->get_last_qt_var('language');
        if (empty($currentlanguage) && $default !== '') {
            $currentlanguage = $default;
        }
        $selectname = $qa->get_qt_field_name('language');
        $selectid = 'id_' . $selectname;
        $html = html_writer::start_tag('div', array('class' => 'sqlrunner-lang-select-div'));
        $html .= html_writer::tag('label',
            sqlrunner_str('languageselectlabel'),
                array('for' => $selectid));
        $html .= html_writer::start_tag('select',
                array('id' => $selectid, 'name' => $selectname,
                      'class' => 'sqlrunner-lang-select', 'required' => ''));
        if (empty($currentlanguage)) {
            $html .= html_writer::tag('option', '', array('value' => ''));
        }
        foreach ($languages as $lang) {
            $attributes = array('value' => $lang);
            if ($lang === $currentlanguage) {
                $attributes['selected'] = 'selected';
            }
            $html .= html_writer::tag('option', $lang, $attributes);
        }
        $html .= html_writer::end_tag('select');
        $html .= html_writer::end_tag('div');
        return $html;
    }


    /**
     *
     * @param qtype_sqlrunner_testing_outcome $outcome
     * @return string the CSS class for the given testing outcome
     */
    protected function results_class($outcome, $isallornothing) {
        if ($outcome->all_correct()) {
            $resultsclass = "sqlrunner-test-results good";
        } else if ($isallornothing || $outcome->mark_as_fraction() == 0) {
            $resultsclass = "sqlrunner-test-results bad";
        } else {
            $resultsclass = 'sqlrunner-test-results partial';
        }
        return $resultsclass;
    }


    // Support method to generate the "Show differences" button.
    // Returns the HTML for the button, and sets up the JavaScript handler
    // for it.
    protected function diff_button($qa) {
        $buttonid = $qa->get_behaviour_field_name('diffbutton');
        $attributes = array(
            'type' => 'button',
            'id' => $buttonid,
            'name' => $buttonid,
            'value' => sqlrunner_str('showdifferences'),
            'class' => 'btn btn-secondary',
        );
        $html = html_writer::empty_tag('input', $attributes);

        $this->page->requires->js_call_amd('qtype_sqlrunner/showdiff',
            'initDiffButton',
            array($attributes['id'],
                  sqlrunner_str('showdifferences'),
                  sqlrunner_str('hidedifferences'),
                  sqlrunner_str('expectedcolhdr'),
                  sqlrunner_str('gotcolhdr')
            )
        );

        return $html;
    }


    /**
     * Support method to generate the "Reset" button, which resets the student
     * answer to the preloaded value.
     *
     * Returns the HTML for the button, and sets up the JavaScript handler
     * for it.
     * @param question_attempt $qa The current question attempt object
     * @param string $responsefieldid The id of the student answer field
     * @param string $preload The text to be plugged into the answer if reset
     * @return string html string for the button
     */
    protected function reset_button($qa, $responsefieldid, $preload) {
        $buttonid = $qa->get_behaviour_field_name('resetbutton');
        $attributes = array(
            'type' => 'button',
            'id' => $buttonid,
            'name' => $buttonid,
            'value' => sqlrunner_str('reset'),
            'class' => 'answer_reset_btn btn btn-secondary',
            'data-reload-text' => $preload);
        $html = html_writer::empty_tag('input', $attributes);

        $this->page->requires->js_call_amd('qtype_sqlrunner/resetbutton',
            'initResetButton',
            array($buttonid,
                  $responsefieldid,
                  sqlrunner_str('confirmreset')
            )
        );

        return $html;
    }

    public function render_video(question_attempt $qa, question_definition $question) {
        $context = [];
        $context['videourl'] = $question->videourl ?? null;
        return $this->output->render_from_template('qtype_sqlrunner/video_layout', $context);
    }
}
