<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the editing form for the coderunner question type.
 *
 * @package 	questionbank
 * @subpackage 	questiontypes
 * @copyright 	&copy; 2013 Richard Lobb
 * @author 		Richard Lobb richard.lobb@canterbury.ac.nz
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot . '/question/type/pythonrunner/question.php');
require_once($CFG->dirroot . '/question/type/sqlrunner/edit_sqlrunner_form.php');

use core_question\local\bank\question_version_status;
use qtype_pythonrunner\constants;
use qbank_managecategories\helper;
use qtype_pythonrunner\python_database_result;

/**
 * CodeRunner editing form definition.
 */
class qtype_pythonrunner_edit_form extends question_edit_form {

    /**
     * @var qtype_pythonrunner_question
     */
    protected $formquestion;

    const RESULT_COLUMNS_SIZE = 80; // The size of the resultcolumns field.

    const RUNNER_TYPE = 'python3';

    const EDITOR_LANG = 'python';

    // penaltyregime field. We hide it from form, but this field is necessary. Get it once
    protected $_penalty = '';

    protected string $acelang = self::EDITOR_LANG;

    protected string $lang = self::EDITOR_LANG;

    public function qtype() {
        return 'pythonrunner';
    }

    // Define the CodeRunner question edit form.
    protected function definition() {
        global $PAGE;
        $mform = $this->_form;
        $this->_penalty = get_config('qtype_pythonrunner', 'default_penalty_regime') ?? '0';

        if (!empty($this->question->options->language)){
            $this->lang = $this->acelang = $this->question->options->language;
        }

        if (!empty($this->question->options->acelang)){
            $this->acelang = $this->question->options->acelang;
        }

        if (is_siteadmin()){
            $mform->addElement('html', '<a href="/admin/settings.php?section=qtypesettingpythonrunner">Edit pythonrunner global preferences</a>');
        }

        $this->make_error_div($mform);
        $mform->addElement('html', '</fieldset> <div style="display:none"><fieldset><div>');
        $this->make_questiontype_panel($mform);
        $this->make_customisation_panel($mform);
        $this->make_advanced_customisation_panel($mform);
        $mform->addElement('html', '</div></fieldset></div><fieldset>');
        qtype_pythonrunner_util::load_ace();

        $PAGE->requires->js_call_amd('qtype_pythonrunner/textareas', 'setupAllTAs');
        $PAGE->requires->js_call_amd('qtype_pythonrunner/authorform', 'initEditForm');
        $PAGE->add_body_class('admin_editor_page');

        $this->parent_definition();  // The superclass adds the "General" stuff.
    }

    protected function parent_definition(){
        $mform = $this->_form;

        // Standard fields at the start of the form.
        $mform->addElement('header', 'generalheader', get_string("general", 'form'));
        $system_ctx = \context_system::instance();
        if (!isset($this->question->id)) {
            if (!empty($this->question->formoptions->mustbeusable)) {
                $contexts = $this->contexts->having_add_and_use();
            } else {
                $contexts = $this->contexts->having_cap('moodle/question:add');
            }
            if(\core\plugininfo\qbank::is_plugin_enabled(helper::PLUGINNAME)){
                // we need to get default system category as default
                // If user not change it - trigger validation message
                /** @noinspection PhpParamsInspection There is a mistake in moodle documentation */
                $sys_cat = helper::get_categories_for_contexts($system_ctx->id, 'sortorder, id', false);
                $sys_cat = reset($sys_cat);
                // default system category value
                $sys_cat_value = $sys_cat->id .','.$sys_cat->contextid;
                $categories = [
                    $sys_cat_value => sqlrunner_str('not_selected')
                ];

                $ctx_cats = [];
                foreach ($contexts as $ctx){
                    if ($ctx->contextlevel != CONTEXT_COURSE){
                        continue;
                    }
                    $q_cat = helper::get_categories_for_contexts($ctx->id, 'sortorder, id', true);
                    $top = current($q_cat);

                    // get only course sub categories (without default)
                    foreach ($q_cat as $child_cat){
                        if ($child_cat->id != $top->id && $child_cat->parent != $top->id){
                            $ctx_cats[$child_cat->id.','.$child_cat->contextid] = $child_cat->name;
                        }
                    }
                }
                if(empty($ctx_cats)){
                    throw new moodle_exception('exception_emptycategory', SQL_RUNNER);
                }
                $options_list_params = array(
                    'placeholder' => sqlrunner_str('select_category'),
                );
                $categories = array_merge($categories, $ctx_cats);

                $mform->addElement('select', 'category', get_string('category', 'question'),
                    $categories, $options_list_params);
                $mform->addRule('category', 'required', 'required', null, 'client');

                $mform->setDefault('category',$sys_cat_value);
            } else {
                // Adding question.
                $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
                    array('contexts' => $contexts));
            }
        } else if (!($this->question->formoptions->canmove ||
            $this->question->formoptions->cansaveasnew)) {
            // Editing question with no permission to move from category.
            $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
                array('contexts' => array($this->categorycontext)));
            $mform->addElement('hidden', 'usecurrentcat', 1);
            $mform->setType('usecurrentcat', PARAM_BOOL);
            $mform->setConstant('usecurrentcat', 1);
        } else {
            // Editing question with permission to move from category or save as new q.
            $currentgrp = array();
            $currentgrp[0] = $mform->createElement('questioncategory', 'category',
                get_string('categorycurrent', 'question'),
                array('contexts' => array($this->categorycontext)));
            // Validate if the question is being duplicated.
            $beingcopied = false;
            if (isset($this->question->beingcopied)) {
                $beingcopied = $this->question->beingcopied;
            }
            if (($this->question->formoptions->canedit ||
                    $this->question->formoptions->cansaveasnew) && ($beingcopied)) {
                // Not move only form.
                $currentgrp[1] = $mform->createElement('checkbox', 'usecurrentcat', '',
                    get_string('categorycurrentuse', 'question'));
                $mform->setDefault('usecurrentcat', 1);
            }
            $currentgrp[0]->freeze();
            $currentgrp[0]->setPersistantFreeze(false);
            $mform->addGroup($currentgrp, 'currentgrp',
                get_string('categorycurrent', 'question'), null, false);

            if (($beingcopied)) {
                $mform->addElement('questioncategory', 'categorymoveto',
                    get_string('categorymoveto', 'question'),
                    array('contexts' => array($this->categorycontext)));
                if ($this->question->formoptions->canedit ||
                    $this->question->formoptions->cansaveasnew) {
                    // Not move only form.
                    $mform->disabledIf('categorymoveto', 'usecurrentcat', 'checked');
                }
            }
        }

        $mform->addElement('text', 'name', get_string('questionname', 'question'),
            array('size' => 50, 'maxlength' => 255));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('editor', 'questiontext', get_string('questiontext', 'question'),
            array('rows' => 15), $this->editoroptions);
        $mform->setType('questiontext', PARAM_RAW);
        $mform->addRule('questiontext', null, 'required', null, 'client');

//        $mform->addElement('select', 'status', get_string('status', 'qbank_editquestion'),
//            \qbank_editquestion\editquestion_helper::get_question_status_list());

        $mform->addElement('hidden', 'status');
        $mform->setType('status', PARAM_TEXT );
        $mform->setDefault('status', question_version_status::QUESTION_STATUS_READY);

        $mform->addElement('hidden', 'defaultmark');
        $mform->setType('defaultmark', PARAM_FLOAT);
        $mform->setDefault('defaultmark', 1);
//        $mform->addRule('defaultmark', null, 'required', null, 'client');

        $mform->addElement('hidden', 'generalfeedback', '');
        $mform->setType('generalfeedback', PARAM_RAW);
        //$mform->addHelpButton('generalfeedback', 'generalfeedback', 'question');

        $mform->addElement('hidden', 'idnumber');
        $mform->setType('idnumber', PARAM_RAW);

        // Any questiontype specific fields.
        $this->definition_inner($mform);

        $mform->addElement('header', 'videoheader', sqlrunner_str('video'));
        $mform->setExpanded('videoheader', 1);
        $mform->addElement('text', 'videourl',
            sqlrunner_str('video_url'));
        $mform->setType('videourl', PARAM_RAW);

        if (core_tag_tag::is_enabled('core_question', 'question')
            && class_exists('qbank_tagquestion\\tags_action_column')
            && \core\plugininfo\qbank::is_plugin_enabled('qbank_tagquestion')) {
            $this->add_tag_fields($mform);
        }

        if ($this->customfieldpluginenabled) {
            // Add custom fields to the form.
            $this->customfieldhandler = qbank_customfields\customfield\question_handler::create();
            $this->customfieldhandler->set_parent_context($this->categorycontext); // For question handler only.
            $this->customfieldhandler->instance_form_definition($mform, empty($this->question->id) ? 0 : $this->question->id);
        }

        $this->add_hidden_fields();

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_COMPONENT);

        $mform->addElement('hidden', 'makecopy');
        $mform->setType('makecopy', PARAM_INT);

        $buttonarray = array();
        if (!empty($this->question->id)){
            $buttonarray[] = $mform->createElement('submit', 'updatebutton', get_string('savechangesandcontinueediting', 'question'));
        }

        // We don't need preview button
//        if ($this->can_preview() && \core\plugininfo\qbank::is_plugin_enabled('qbank_previewquestion')){
//            $previewlink = $PAGE->get_renderer('qbank_previewquestion')->question_preview_link(
//                $this->question->id, $this->context, true);
//            $buttonarray[] = $mform->createElement('static', 'previewlink', '', $previewlink);
//        }

        $mform->addGroup($buttonarray, 'updatebuttonar', '', array(' '), false);
        $mform->closeHeaderBefore('updatebuttonar');

        $this->add_action_buttons(true, get_string('savechanges'));

        if ((!empty($this->question->id)) && (!($this->question->formoptions->canedit ||
                $this->question->formoptions->cansaveasnew))) {
            $mform->hardFreezeAllVisibleExcept(array('categorymoveto', 'buttonar', 'currentgrp'));
        }
    }

    // Defines the bit of the CodeRunner question edit form after the "General"
    // section and before the footer stuff.
    public function definition_inner($mform) {
        $this->add_sample_answer_field($mform);

        // Confusion alert! A call to $mform->setDefault("mark[$i]", '1.0') looks
        // plausible and works to set the empty-form default, but it then
        // overrides (rather than is overridden by) the actual value. The same
        // thing happens with $repeatedoptions['mark']['default'] = 1.000 in
        // get_per_testcase_fields (q.v.).
        // I don't understand this (but see 'Evil hack alert' in the baseclass).
        // MY EVIL HACK ALERT (OLD: probably out of date ) -- setting just $numTestcases default values
        // fails when more test cases are added on the fly. So I've set up
        // enough defaults to handle 5 successive adding of more test cases.
        // I believe this is a bug in the underlying Moodle question type, not
        // mine, but ... how to be sure?
        $this->_create_test_cases($mform);
        $count = qtype_pythonrunner::BASE_SQL_QUERIES_COUNT;
        if (!empty($this->question)){
            $count = !empty($this->question->options->sql_queries) ? count($this->question->options->sql_queries) : $count;
        }
        $this->add_per_sql_query_fields($mform, $count);

        $mform->addElement('hidden', 'attachments');
        $mform->setType('attachments', PARAM_INT);
        $mform->setDefaults([
            'attachments' => 0,
            'mark' => 1,
            'ordering' => 1,
        ]);
    }

    /**
     * Add a field for a sample answer to this problem (optional)
     * @param MoodleQuickForm $mform the form being built
     */
    protected function add_sample_answer_field($mform) {
        $mform->addElement('header', 'answerhdr', pythonrunner_str('answer'), '');
        $mform->setExpanded('answerhdr', 1);

        $mform->addElement('select', 'pythontype',  pythonrunner_str('pythontype'), [
            PYTHON_OTHER => pythonrunner_str('pythontype:python_other'),
            PYTHON_ALGO  => pythonrunner_str('pythontype:python_algo'),
        ]);
        $mform->setType('pythontype', PARAM_INT);

        $attributes = array(
            'rows' => 9,
            'class' => 'answer edit_code',
            'data-params' => $this->get_merged_ui_params(),
            'data-lang' => $this->acelang
        );

        $mform->addElement( 'textarea', 'answer', pythonrunner_str('answer'), $attributes);
        // Add a file attachment upload panel (disabled if attachments not allowed).

        $mform->addElement('advcheckbox', 'validateonsave', null, sqlrunner_str('validateonsave'));
        $mform->setDefault('validateonsave', true);
        $mform->addHelpButton('answer', 'answer', PYTHON_RUNNER);
    }

    /**
     * Add a set of form fields, obtained from get_per_test_fields, to the form,
     * one for each existing testcase, with some blanks for some new ones
     * This overrides the base-case version because we're dealing with test
     * cases, not answers.
     * @param object $mform the form being built.
     * @param int $repeat_count count repeats (default).
     */
    protected function add_per_sql_query_fields($mform, $repeat_count) {
        $mform->addElement('header', 'sql_queries_header', pythonrunner_str('sql_queries'), '');
        $mform->setExpanded('sql_queries_header', 1);
        $repeatedoptions = array();
        $repeated = $this->get_per_sql_query_fields($mform, $repeatedoptions);
        $this->repeat_elements($repeated, $repeat_count, $repeatedoptions,
            'numtestcases', 'addanswers', QUESTION_NUMANS_ADD,
            $this->get_more_choices_string(), true);
    }

    /**
     *  A rewritten version of get_per_answer_fields specific to test cases.
     */
    public function get_per_sql_query_fields($mform, &$repeatedoptions) {
        $repeated = array();
        $repeated[] = $mform->createElement('text', 'fieldname', 'Field name {no}');
        $repeated[] = $mform->createElement('textarea', 'sqlquery', 'Sql query {no}', array('rows' => 3, 'class' => 'edit_code'));
        $repeated[] = $mform->createElement('hidden', 'sqlexpected', '');
        $repeated[] = $mform->createElement('hidden', 'lastcode', '');

        $repeatedoptions['fieldname']['type'] = PARAM_RAW;
        $repeatedoptions['sqlquery']['type'] = PARAM_RAW;
        $repeatedoptions['sqlexpected']['type'] = PARAM_RAW;
        $repeatedoptions['lastcode']['type'] = PARAM_TEXT;

        return $repeated;
    }

    /**
     *  A rewritten version of get_per_answer_fields specific to test cases.
     */
    protected function _create_test_cases($mform) {
        $mform->addElement('hidden', 'testcode', '');
        $mform->setType('testcode', PARAM_RAW);

        $mform->addElement('hidden', 'stdin', '');
        $mform->setType('stdin', PARAM_RAW);

        $mform->addElement('hidden', 'expected', '', array('id' => 'id_expected_0'));
        $mform->setType('expected', PARAM_RAW);

        $mform->addElement('hidden', 'extra', '');
        $mform->setType('extra', PARAM_RAW);

        $mform->addElement('hidden', 'useasexample', 0);
        $mform->setType('useasexample', PARAM_INT);

        $mform->addElement('hidden', 'display', 0);
        $mform->setType('display', PARAM_INT);

        $mform->addElement('hidden', 'hiderestiffail', 0);
        $mform->setType('hiderestiffail', PARAM_INT);

        $mform->addElement('hidden', 'mark', 1);
        $mform->setType('mark', PARAM_FLOAT);

        $mform->addElement('hidden', 'ordering', 0);
        $mform->setType('ordering', PARAM_INT);

        $mform->addElement('hidden', 'testtype', '');
        $mform->setType('testtype', PARAM_RAW);
    }

    public function set_data($question) {
        // set empty value to avoid empty notice in parent function
        $question->generalfeedback = array();
        $question->generalfeedback['text'] = '';

        parent::set_data($question);
    }

    public function data_preprocessing($question) {
        // Preprocess the question data to be loaded into the form. Called by set_data after
        // standard stuff all loaded.
        // TODO - consider how much of this can be dispensed with just by
        // calling question_bank::loadquestion($question->id).
        global $COURSE;

        if (!isset($question->brokenquestionmessage)) {
            $question->brokenquestionmessage = '';
        }
        if (isset($question->options->testcases) || isset($question->options->sql_queries)) { // Reloading a saved question?
            // Firstly check if we're editing a question with a missing prototype.
            // Set the broken_question message if so.
            $q = $this->make_question_from_form_data($question);
            if ($q->prototype === null){
                $question->brokenquestionmessage = pythonrunner_str('missingprototype', array('crtype' => $question->pythonrunnertype));
            }

            // Record the prototype for subsequent use.
            $question->prototype = $q->prototype;

            if (isset($question->options->testcases)){
                // Next flatten all the question->options down into the question itself.
                foreach ($question->options->testcases as $tc){
                    $question->testcode = $this->newline_hack($tc->testcode);
                    $question->testtype = $tc->testtype;
                    $question->stdin = $this->newline_hack($tc->stdin);
                    $question->expected = $tc->expected;
                    $question->extra = $this->newline_hack($tc->extra);
                    $question->useasexample = $tc->useasexample;
                    $question->display = $tc->display;
                    $question->hiderestiffail = $tc->hiderestiffail;
                    $question->mark = sprintf("%.3f", $tc->mark);
                }

                // The customise field isn't listed as an extra-question-field so also
                // needs to be copied down from the options here.
                $question->customise = $question->options->customise;

                // Save the prototypetype so can see if it changed on post-back.
                $question->saved_prototype_type = $question->prototypetype;
                $question->courseid = $COURSE->id;

                // Load the type-name if this is a prototype, else make it blank.
                if ($question->prototypetype != 0){
                    $question->typename = $question->pythonrunnertype;
                } else {
                    $question->typename = '';
                }

                // Convert raw newline chars in testsplitterre into 2-char form
                // so they can be edited in a one-line entry field.
                if (isset($question->testsplitterre)){
                    $question->testsplitterre = str_replace("\n", '\n', $question->testsplitterre);
                }

                // Legacy questions may have a question.penalty but no penalty regime.
                // Dummy up a penalty regime from the question.penalty in such cases.
                if (empty($question->penaltyregime)){
                    if (empty($question->penalty) || $question->penalty == 0){
                        $question->penaltyregime = '0';
                    } else {
                        if (intval(100 * $question->penalty) == 100 * $question->penalty){
                            $decdigits = 0;
                        } else {
                            $decdigits = 1;  // For nasty fractions like 0.33333333.
                        }
                        $penaltypercent = number_format($question->penalty * 100, $decdigits);
                        $penaltypercent2 = number_format($question->penalty * 200, $decdigits);
                        $question->penaltyregime = $penaltypercent.', '.$penaltypercent2.', ...';
                    }
                }
            }

            if (isset($question->options->sql_queries)){
                $question->sqlquery = [];
                $question->fieldname = [];
                $question->sqlexpected = [];
                $question->lastcode = [];

                // Next flatten all the question->options down into the question itself.
                foreach ($question->options->sql_queries as $key => $sq){
                    $question->sqlquery[$key] = $sq->query;
                    $question->lastcode[$key] = $sq->query;
                    $question->fieldname[$key] = $sq->fieldname;
                    $question->sqlexpected[$key] = json_encode([
                        'fields' => json_decode($sq->fields),
                        'data'   => json_decode($sq->data),
                    ]);
                }
            }
        } else {
            // This is a new question.
            $question->penaltyregime = get_config(PYTHON_RUNNER, 'default_penalty_regime');
        }

        $question->generalfeedback = '';
        return $question;
    }

    // A horrible hack for a horrible browser "feature".
    // Inserts a newline at the start of a text string that's going to be
    // displayed at the start of a <textarea> element, because all browsers
    // strip a leading newline. If there's one there, we need to keep it, so
    // the extra one ensures we do. If there isn't one there, this one gets
    // ignored anyway.
    private function newline_hack($s) {
        return "\n" . $s;
    }

    // FUNCTIONS TO BUILD PARTS OF THE MAIN FORM
    // =========================================.

    // Create an empty div with id id_qtype_pythonrunner_error_div for use by
    // JavaScript error handling code.
    private function make_error_div($mform) {
        $mform->addElement('html', "<div id='id_qtype_pythonrunner_error_div' class='qtype_pythonrunner_error_message'></div>");
    }

    /**
     * Add to the supplied $mform the panel "Coderunner question type".
     *
     * @param MoodleQuickForm $mform
     */
    protected function make_questiontype_panel($mform) {
//        list($languages, $types) = $this->get_languages_and_types();

        // Insert the (possible) missing prototype message as a hidden field. JavaScript
        // will be used to show it if non-empty.
        $mform->addElement('hidden', 'brokenquestionmessage', '', array('id' => 'id_broken_question', 'class' => 'brokenquestionerror'));
        $mform->setType('brokenquestionmessage', PARAM_RAW);

        // The Question Type controls (a group with just a single member).
        $mform->addElement('hidden', 'pythonrunnertype');
        $mform->setType('pythonrunnertype', PARAM_RAW);

        $mform->addElement('hidden', 'hidecheck');
        $mform->setType('hidecheck', PARAM_INT);

        $mform->addElement('hidden', 'giveupallowed');
        $mform->setType('giveupallowed', PARAM_INT);

        $mform->addElement('hidden', 'displayfeedback');
        $mform->setType('displayfeedback', PARAM_INT);

        $mform->addElement('hidden', 'uiparameters', '', array('id' => 'id_uiparameters'));
        $mform->setType('uiparameters', PARAM_RAW);

        // Template params.
        $mform->addElement('hidden', 'templateparams', '', array('id' => 'id_templateparams'));
        $mform->setType('templateparams', PARAM_RAW);

        $mform->setDefaults([
            'pythonrunnertype' => static::RUNNER_TYPE,
            'hidecheck' => '0',
            'giveupallowed' => constants::GIVEUP_ALWAYS,
            'displayfeedback' => constants::FEEDBACK_SHOW,
            'templateparams' => '',
        ]);
    }

    /**
     * Add to the supplied $mform the Customisation Panel
     * The panel is hidden by default but exposed when the user clicks
     * the 'Customise' checkbox in the question-type panel.
     *
     * @param $mform MoodleQuickForm
     */
    protected function make_customisation_panel($mform) {
        // The following fields are used to customise a question by overriding
        // values from the base question type. All are hidden
        // unless the 'customise' checkbox is checked.

        $mform->addElement('header', 'customisationheader', pythonrunner_str('customisation'));
        $mform->addElement('hidden', 'template');
        // set checked = true for authform.js to show customisation section
//        $mform->addElement('hidden', 'customise', 1, ['id' => 'id_customise', 'checked' => true]);
        $mform->setType('template', PARAM_TEXT);

        $templatecontrols = array();
        $templatecontrols[] = $mform->createElement('advcheckbox', 'iscombinatortemplate', null, pythonrunner_str('iscombinatortemplate'));
        $templatecontrols[] = $mform->createElement('advcheckbox', 'allowmultiplestdins', null, pythonrunner_str('allowmultiplestdins'));

        $templatecontrols[] = $mform->createElement('text', 'testsplitterre', pythonrunner_str('testsplitterre'), array('size' => 45));
        $mform->setType('testsplitterre', PARAM_RAW);

        $mform->addElement('group', 'templatecontrols', sqlrunner_str('templatecontrols'), $templatecontrols, null, false);
        $mform->addHelpButton('templatecontrols', 'templatecontrols', SQL_RUNNER);

        $gradingcontrols = array();
        $gradertypes = array(
            'SqlEqualityGrader'    => sqlrunner_str('sqlequalitygrader'),
            'PythonEqualityGrader' => pythonrunner_str('pythonequalitygrader'),
            'EqualityGrader'       => pythonrunner_str('equalitygrader'),
        );

        $gradingcontrols[] = $mform->createElement('select', 'grader', null, $gradertypes);
        $mform->addElement('group', 'gradingcontrols', sqlrunner_str('grading'), $gradingcontrols, null, false);
        $mform->addHelpButton('gradingcontrols', 'gradingcontrols', SQL_RUNNER);

        $mform->addElement('text', 'resultcolumns', sqlrunner_str('resultcolumns'), array('size' => self::RESULT_COLUMNS_SIZE));
        $mform->setType('resultcolumns', PARAM_RAW);
        $mform->addHelpButton('resultcolumns', 'resultcolumns', SQL_RUNNER);

        $uicontrols = array();
        $plugins = qtype_sqlrunner_ui_plugins::get_instance();
        $uitypes = $plugins->dropdownlist();

        $uicontrols[] = $mform->createElement('select', 'uiplugin', sqlrunner_str('student_answer'), $uitypes);
        $uicontrols[] = $mform->createElement('advcheckbox', 'useace', null, sqlrunner_str('useace'));
        $mform->addElement('group', 'uicontrols', sqlrunner_str('uicontrols'), $uicontrols, null, false);
        $mform->addHelpButton('uicontrols', 'uicontrols', SQL_RUNNER);

        $attributes = array('rows' => 5,'class' => 'prototypeextra edit_code');
        $mform->addElement('textarea', 'prototypeextra', sqlrunner_str('prototypeextra'), $attributes);
        $mform->addHelpButton('prototypeextra', 'prototypeextra', SQL_RUNNER);

        $mform->setExpanded('customisationheader');  // Although expanded it's hidden until JavaScript unhides it .

        $mform->setDefaults([
            'grader' => 'PythonEqualityGrader',
            'iscombinatortemplate' => 1,
            'template' => '{{STUDENT_ANSWER}}',
            'uiplugin' => 'ace',
            'useace' => true
        ]);
    }

    // Make the advanced customisation panel, also hidden until the user
    // customises the question. The fields in this part of the form are much more
    // advanced and not recommended for most users.
    private function make_advanced_customisation_panel($mform) {
        $mform->addElement('header', 'advancedcustomisationheader',
            pythonrunner_str('advanced_customisation'));

        $prototypecontrols = array();

        $prototypeselect = $mform->createElement('select', 'prototypetype', pythonrunner_str('prototypeQ'));
        $prototypeselect->addOption('No', '0');
        $prototypeselect->addOption('Yes (built-in)', '1', array('disabled' => 'disabled'));
        $prototypeselect->addOption('Yes (user defined)', '2');

        $prototypecontrols[] = $prototypeselect;
        $prototypecontrols[] = $mform->createElement('text', 'typename', pythonrunner_str('typename'), array('size' => 30));
        $mform->addElement('group', 'prototypecontrols', sqlrunner_str('prototypecontrols'), $prototypecontrols, null, false);
        $mform->setType('typename', PARAM_RAW_TRIMMED);

        $mform->addElement('hidden', 'saved_prototype_type');
        $mform->setType('saved_prototype_type', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('prototypecontrols', 'prototypecontrols', SQL_RUNNER);

        $sandboxcontrols = array();

        $enabled = qtype_pythonrunner_sandbox::enabled_sandboxes();
        if (count($enabled) > 1) {
            $sandboxes = array_merge(array('DEFAULT' => 'DEFAULT'), $enabled);
            foreach ($sandboxes as $ext => $class) {
                $sandboxes[$ext] = $ext;
            }

            $sandboxcontrols[] = $mform->createElement('select', 'sandbox', null, $sandboxes);
        } else {
            $sandboxcontrols[] = $mform->createElement('hidden', 'sandbox', 'DEFAULT');
            $mform->setType('sandbox', PARAM_RAW);
        }

        $sandboxcontrols[] = $mform->createElement('text', 'cputimelimitsecs', sqlrunner_str('cputime'), array('size' => 3));
        $sandboxcontrols[] = $mform->createElement('text', 'memlimitmb', sqlrunner_str('memorylimit'), array('size' => 5));
        $sandboxcontrols[] = $mform->createElement('text', 'sandboxparams', sqlrunner_str('sandboxparams'), array('size' => 15));
        $mform->addElement('group', 'sandboxcontrols', sqlrunner_str('sandboxcontrols'), $sandboxcontrols, null, false);

        $mform->setType('cputimelimitsecs', PARAM_RAW);
        $mform->setType('memlimitmb', PARAM_RAW);
        $mform->setType('sandboxparams', PARAM_RAW);
        $mform->addHelpButton('sandboxcontrols', 'sandboxcontrols', SQL_RUNNER);

        $mform->addElement('hidden', 'language', static::RUNNER_TYPE);
        $mform->setType('language', PARAM_RAW_TRIMMED);

        $mform->addElement('hidden', 'acelang', static::EDITOR_LANG);
        $mform->setType('acelang', PARAM_RAW_TRIMMED);

        // IMPORTANT: authorform.js has to set the initial enabled/disabled
        // status of the testsplitterre and allowmultiplestdins elements
        // after loading a new question type as the following code apparently
        // sets up event handlers only for clicks on the iscombinatortemplate
        // checkbox.
        $mform->disabledIf('typename', 'prototypetype', 'neq', '2');
        $mform->disabledIf('testsplitterre', 'iscombinatortemplate', 'eq', 0);
        $mform->disabledIf('allowmultiplestdins', 'iscombinatortemplate', 'eq', 0);

        $mform->setDefaults([
            'prototypetype' => '0',
            'cputimelimitsecs' => 5,
            'memlimitmb' => 200,
        ]);
    }

    /***********************************************************
     *
     * VALIDATION.
     *
     **********************************************************/

    // Validate the given data and possible files.
    public function validation($data, $files) {
        $data['penaltyregime'] = $this->_penalty; // hard code penalty value, because it is not necessacary field for our logic
        [$catid, $ctx] = explode(',', $data['category']);
        if ($ctx == \context_system::instance()->id){
            return ['category' => 'Category field is required'];
        }

        $errors = parent::validation($data, $files);
        $this->formquestion = $this->make_question_from_form_data($data);
        if ($data['pythonrunnertype'] == 'Undefined') {
            $errors['pythonrunner_type_group'] = pythonrunner_str('questiontype_required');
            return $errors;  // Don't continue checking in this case. Template param validation breaks.
        }

        $this->validate_template_params($errors, $data);

        if (isset($data['uiparameters']) && $data['uiparameters']) {
            $uiparametererrors = $this->validate_ui_parameters($data['uiparameters']);
            if ($uiparametererrors) {
                $errors['uiparametergroup'] = $uiparametererrors;
            }
        }

        $resultcolumnsjson = trim($data['resultcolumns']);
        if ($resultcolumnsjson !== '') {
            $resultcolumns = json_decode($resultcolumnsjson);
            if ($resultcolumns === null) {
                $errors['resultcolumns'] = sqlrunner_str('resultcolumnsnotjson');
            } else if (!is_array($resultcolumns)) {
                $errors['resultcolumns'] = sqlrunner_str('resultcolumnsnotlist');
            } else {
                foreach ($resultcolumns as $col) {
                    if (!is_array($col) || count($col) < 2) {
                        $errors['resultcolumns'] = sqlrunner_str('resultcolumnspecbad');
                        break;
                    }
                    foreach ($col as $el) {
                        if (!is_string($el)) {
                            $errors['resultcolumns'] = sqlrunner_str('resultcolumnspecbad');
                            break;
                        }
                    }
                }
            }
        }

        if (count($errors) == 0 && !empty($data['validateonsave'])) {
            $testresult = $this->validate_sample_answer();
            if ($testresult) {
                $errors['answer'] = $testresult;
            }
        }

        $acelangs = trim($data['acelang']);
        if ($acelangs !== '' && strpos($acelangs, ',') !== false) {
            $parsedlangs = qtype_pythonrunner_util::extract_languages($acelangs);
            if ($parsedlangs === false) {
                $errors['languages'] = pythonrunner_str('multipledefaults');
            } else if (count($parsedlangs[0]) === 0) {
                $errors['languages'] = pythonrunner_str('badacelangstring');
            }
        }

        return $errors;
    }

    // Check the templateparameters value, if given. Return an array containing
    // the error message string, which will be empty if there are no errors,
    // and the JSON evaluated template parameters, which will be empty if there
    // are errors.
    protected function validate_template_params(&$errors, $data) {
        if ($data['pythontype'] != PYTHON_OTHER){
            return;
        }

        $runner = new qtype_sqlrunner_jobrunner();
        $runner->set_outcome_class(qtype_pythonrunner_sql_testing_outcome::class);
        $runner->add_additional_param(qtype_sqlrunner_jobrunner::SANDBOX_PARAMS_NAME, [
            qtype_sqlrunner_sqlsandbox::SQL_PARAM_RESULT => python_database_result::class,
        ]);
        $template = '';
        foreach ($data['sqlquery'] as $key => $code){
            if (empty($code)){
                continue;
            }
            $fieldname = preg_replace('/\s+/', '', $data['fieldname'][$key]);
            if (empty($fieldname)){
                $errors["fieldname[$key]"] = 'Field name cannot be empty';
            }

            if (!empty($data['sqlexpected'][$key]) && $code == $data['lastcode'][$key]){
                if (empty($fieldname)){
                    continue;
                }

                $fieldname = $data['fieldname'][$key];
                $template .= "$fieldname=pd.read_json('{{{$fieldname}}}')".PHP_EOL;
                continue;
            }

            $response['answer'] = $code;
            $question = qtype_sqlrunner_question::get_empty_question($code, $data['sqlexpected'][$key], $key);

            $outcome = $question->run($response, false, qtype_sqlrunner_edit_form::EDITOR_LANG, $runner);
            $result = $outcome->validation_error_message();
            // this is the easiest way to rename classes and ids
            $errors["sqlquery[$key]"] = $result;
        }
        if (empty($errors)){
            $this->formquestion->template = 'import pandas as pd'.PHP_EOL.$template.PHP_EOL.'{{STUDENT_ANSWER}}';
        }
    }

    // Check that the uiparameters field, if present and non-empty, is valid.
    // Return an error message string if not valid, else an empty string.
    protected function validate_ui_parameters($uiparameters) {
        $checkmissing = false; // True to check for missing parameters. Currently not doing this.
        $errormessage = '';
        if (empty($uiparameters)) {
            return $errormessage;
        }
        $json = '';
        try {
            $decoded = json_decode($uiparameters, true);
        } catch (Exception $e) {
            $decoded = null;
        }
        if ($decoded === null) {
               $errormessage = pythonrunner_str('baduiparams');
        } else {
            // Check only valid uiparameters are defined.
            $uipluginname = $this->formquestion->uiplugin;
            $uiplugins = qtype_sqlrunner_ui_plugins::get_instance();
            $uiparams = $uiplugins->parameters($uipluginname);
            $alluiparamnames = $uiparams->all_names();
            $badparams = array();
            foreach (array_keys($decoded) as $paramname) {
                if (!in_array($paramname, $alluiparamnames)) {
                    $badparams[] = $paramname;
                }
            }
            if ($badparams) {
                $errormessage = pythonrunner_str('illegaluiparamname', array('uiname' => $uipluginname)) . implode(', ', $badparams);
            } else if ($checkmissing) {
                // Make sure any required ui parameters are defined.
                $missingparams = array();
                foreach ($alluiparamnames as $uiname) {
                    if ($uiparams->is_required($uiname) && !in_array($uiname, array_keys($decoded))) {
                        $missingparams[] = $uiname;
                    }
                }
                if ($missingparams) {
                    $errormessage = pythonrunner_str('missinguiparams') . implode(', ', $missingparams);
                }
            }
        }

        return $errormessage;
    }

    /**
     * Validate the test cases.
     *
     * Base validation @see static::_default_validate_test_cases()
     */
    protected function validate_test_cases($data) {
        $errors = array(); // Return value.
        $testcodes = [$data['answer']];
        $stdins = [''];
        $expecteds = [''];
        $marks = [1];
        $count = $numnonemptytests = 0;
        $num = 1; // set only one test case that equals 'answer' field
        for ($i = 0; $i < $num; $i++){
            $testcode = trim($testcodes[$i]);
            if ($testcode != ''){
                $numnonemptytests++;
            }
            $stdin = trim($stdins[$i]);
            $expected = trim($expecteds[$i]);
            if ($testcode !== '' || $stdin != '' || $expected !== ''){
                $count++;
                $mark = trim($marks[$i]);
                if ($mark != ''){
                    if (!is_numeric($mark)){
                        $errors["testcode[$i]"] = pythonrunner_str('nonnumericmark');
                    } else if (floatval($mark) <= 0){
                        $errors["testcode[$i]"] = pythonrunner_str('negativeorzeromark');
                    }
                }
            }
        }

        if ($count == 0){
            $errors["testcode[0]"] = pythonrunner_str('atleastonetest');
        } elseif ($numnonemptytests != 0 && $numnonemptytests != $count) {
            $errors["testcode[0]"] = pythonrunner_str('allornone');
        }
        return $errors;
    }

    /**
     * CodeRunner test case Validation.
     */
    protected function _default_validate_test_cases($data) {
        $errors = array(); // Return value.
        $testcodes = $data['testcode'];
        $stdins = $data['stdin'];
        $expecteds = $data['expected'];
        $marks = $data['mark'];
        $count = 0;
        $numnonemptytests = 0;
        $num = max(count($testcodes), count($stdins), count($expecteds));
        for ($i = 0; $i < $num; $i++) {
            $testcode = trim($testcodes[$i]);
            if ($testcode != '') {
                $numnonemptytests++;
            }
            $stdin = trim($stdins[$i]);
            $expected = trim($expecteds[$i]);
            if ($testcode !== '' || $stdin != '' || $expected !== '') {
                $count++;
                $mark = trim($marks[$i]);
                if ($mark != '') {
                    if (!is_numeric($mark)) {
                        $errors["testcode[$i]"] = pythonrunner_str('nonnumericmark');
                    } else if (floatval($mark) <= 0) {
                        $errors["testcode[$i]"] = pythonrunner_str('negativeorzeromark');
                    }
                }
            }
        }

        if ($count == 0) {
            $errors["testcode[0]"] = pythonrunner_str('atleastonetest');
        } else if ($numnonemptytests != 0 && $numnonemptytests != $count) {
            $errors["testcode[0]"] = pythonrunner_str('allornone');
        }
        return $errors;
    }

    /**
     * Check the sample answer (if there is one).
     * Return an empty string if there is no sample answer and no attachments,
     * or if the sample answer passes all the tests.
     * Otherwise return a suitable error message for display in the form.
     */
    protected function validate_sample_answer() {
        $answer = $this->formquestion->answer;
        if (trim($answer) === ''){
            return ''; // Empty answer and no attachments.
        }

        try {
            if (!isset($this->formquestion->uiparameters)){
                $this->formquestion->uiparameters = null; // If hidden, value isn't recorded in formquestion.
            }

            $this->formquestion->start_attempt();
            $response = array('answer' => $this->formquestion->answer);
            $response['language'] = !empty($answerlang) ? $answerlang : static::RUNNER_TYPE;

            list($mark, $state, $cachedata) = $this->formquestion->grade_response($response, false, true);
        } catch (Exception $e){
            return $e->getMessage();
        }

        // Return either an empty string if run was good or an error message.
        if ($mark == 1.0){
            return '';
        }

        $outcome = unserialize($cachedata['_testoutcome']);
        if (!empty($cachedata['_runneroutput']) && !empty($outcome->testresults)){
            // get student output to another step, because test_outcome is serialised and big strings are snip.
            // also sql_result can be encoded and contains non-ASCII symbols, which already brokes while serialise
            $outcome->testresults[0]->got = $cachedata['_runneroutput'];
        }
        $outcome->grader = $this->formquestion->get_grader();
        $error = $outcome->validation_error_message();
        return $error;
    }

    // UTILITY FUNCTIONS.
    // =================.

    protected function make_question_from_form_data($data) {
        // Construct a question object containing all the fields from $data.
        // Used in data pre-processing and when validating a question.
        global $DB, $USER;
        $question = new qtype_pythonrunner_question();
        foreach ($data as $key => $value) {
            if ($key === 'questiontext') {
                // Question text and general feedback are associative arrays.
                $question->$key = $value['text'];
            } else {
                $question->$key = $value;
            }
        }
        $question->answer = trim($question->answer);
        $question->isnew = true;
        $question->student = new qtype_sqlrunner_student($USER);
        $question->iscombinatortemplate = 1;

        // Clean the question object, get inherited fields.
        $qtype = new qtype_pythonrunner();
        $qtype->clean_question_form($question, true);
        $questiontype = $question->pythonrunnertype;
        list($category) = explode(',', $question->category);
        $contextid = $DB->get_field('question_categories', 'contextid', array('id' => $category));
        $question->contextid = $contextid;
        $context = context::instance_by_id($contextid, IGNORE_MISSING);
        $question->prototype = $qtype->get_prototype($questiontype, $context);
        $qtype->set_inherited_fields($question, $question->prototype);
        return $question;
    }

    // Returns the Json for the merged template parameters.
    // It is assumed that this function is called only when a question is
    // initially loaded from the DB or a new question is being created,
    // so that it can use the question bank's load_question method to get
    // a valid question from the DB rather than the stdClass 'question'
    // provided to the form at initialisation.
    protected function get_merged_ui_params(){
        global $USER;
        if (isset($this->cacheduiparamsjson)){
            return $this->cacheduiparamsjson;
        }

        $q = $this->question;
        if (!isset($q->options)){
            return '{}';
        }

        // Editing an existing question.
        try {
            $qfromdb = question_bank::load_question($q->id);
            $qfromdb->student = new qtype_sqlrunner_student($USER);
            $seed = 1;
            $qfromdb->evaluate_question_for_display($seed, null);
            if ($qfromdb->mergeduiparameters){
                $json = json_encode($qfromdb->mergeduiparameters);
            } else {
                $json = '{}';
            }
        } catch (Throwable $e){
            $json = '{}';  // This shouldn't happen, but has been known to.
            $q->brokenquestionmessage = pythonrunner_str('corruptuiparams');
        };
        $this->cacheduiparamsjson = $json;
        return $json;
    }
}