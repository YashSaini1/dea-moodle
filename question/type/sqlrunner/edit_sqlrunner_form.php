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

require_once($CFG->dirroot . '/question/type/sqlrunner/questiontype.php');
require_once($CFG->dirroot . '/question/type/sqlrunner/question.php');

use core_question\local\bank\question_version_status;
use qtype_sqlrunner\constants;
use qbank_managecategories\helper;

/**
 * CodeRunner editing form definition.
 */
class qtype_sqlrunner_edit_form extends question_edit_form {

    const NUM_TESTCASES_START = 2;  // Num empty test cases with new questions.
    const NUM_TESTCASES_ADD = 3;    // Extra empty test cases to add.
    const DEFAULT_NUM_ROWS = 11;    // Answer box rows.
    const DEFAULT_NUM_COLS = 100;   // Answer box columns.
    const TEMPLATE_PARAM_ROWS = 5;  // The number of rows of the template parameter field.
    const UI_PARAM_ROWS = 5;  // The number of rows of the template parameter field.
    const RESULT_COLUMNS_SIZE = 80; // The size of the resultcolumns field.

    const RUNNER_TYPE = 'sql';

    const EDITOR_LANG = self::RUNNER_TYPE;

    // penaltyregime field. We hide it from form, but this field is necessary. Get it once
    protected $_penalty = '';

    public function qtype() {
        return 'sqlrunner';
    }

    // Define the CodeRunner question edit form.
    protected function definition() {
        global $PAGE;
        $mform = $this->_form;
        $this->_penalty = get_config('qtype_sqlrunner', 'default_penalty_regime') ?? '0';

        if (!empty($this->question->options->language)){
            $this->lang = $this->acelang = $this->question->options->language;
        } else {
            $this->lang = $this->acelang = static::EDITOR_LANG;
        }
        if (!empty($this->question->options->acelang)){
            $this->acelang = $this->question->options->acelang;
        }
        if (is_siteadmin()){
            $mform->addElement('html', '<a href="/admin/settings.php?section=qtypesettingsqlrunner">Edit SqlRunner global preferences</a>');
        }

        $this->make_error_div($mform);
        $mform->addElement('html', '</fieldset> <div style="display:none"><fieldset><div>');
        $this->make_questiontype_panel($mform);
        $this->make_questiontype_help_panel($mform);
        $this->make_customisation_panel($mform);
        $this->make_advanced_customisation_panel($mform);
        $mform->addElement('html', '</div></fieldset></div><fieldset>');
        qtype_sqlrunner_util::load_ace();

        $PAGE->requires->js_call_amd('qtype_sqlrunner/textareas', 'setupAllTAs');
        $PAGE->requires->js_call_amd('qtype_sqlrunner/authorform', 'initEditForm');
        $PAGE->add_body_class('admin_editor_page');

        $this->parent_definition($mform);  // The superclass adds the "General" stuff.
    }

    protected function parent_definition(){
        global $DB, $PAGE;
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
                    throw new moodle_exception('exception_emptycategory');
                }
                $options_list_params = array(
                    'placeholder'       => sqlrunner_str('select_category'),
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
       // $this->add_preload_answer_field($mform);
//        $this->add_globalextra_field($mform);

//        if (isset($this->question->options->testcases)) {
//            $numtestcases = count($this->question->options->testcases);
//        } else {
//            $numtestcases = self::NUM_TESTCASES_START;
//        }

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
//        $mform->setDefault('mark', array_fill(0, $numtestcases + 5 * self::NUM_TESTCASES_ADD, 1.0));
        $mform->setDefault('mark', 1);
//        $ordering = array();
//        for ($i = 0; $i < $numtestcases + 5 * self::NUM_TESTCASES_ADD; $i++) {
//            $ordering[] = 10 * ($i + 1);
//        }
        $mform->setDefault('ordering', 1);

        $this->_create_test_cases($mform);
        //$this->add_per_testcase_fields($mform, sqlrunner_str('testcase', "{no}"), $numtestcases);

        // Add the option to attach runtime support files, all of which are
        // copied into the working directory when the expanded template is
        // executed. The file context is that of the current course.
//        $options = $this->fileoptions;
//        $options['subdirs'] = false;
//        $mform->addElement('header', 'fileheader', sqlrunner_str('fileheader'));
//        $mform->addElement('filemanager', 'datafiles', sqlrunner_str('datafiles'), null, $options);

        // Insert the attachment section to allow file uploads.
//        $qtype = question_bank::get_qtype('sqlrunner');
        $mform->addElement('header', 'videoheader', sqlrunner_str('video'));
        $mform->setExpanded('videoheader', 1);
        $mform->addElement('text', 'videourl',
            sqlrunner_str('video_url'));
        $mform->setType('videourl', PARAM_RAW);

        $mform->addElement('hidden', 'attachments');
        $mform->setType('attachments', PARAM_INT);
        $mform->setDefault('attachments', 0);
//        $mform->addHelpButton('attachments', 'allowattachments', 'qtype_sqlrunner');

//        $mform->addElement('select', 'attachmentsrequired', sqlrunner_str('attachmentsrequired'), $qtype->attachments_required_options());
//        $mform->setDefault('attachmentsrequired', 0);
//        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_sqlrunner');
//        $mform->disabledIf('attachmentsrequired', 'attachments', 'eq', 0);
//
//        $filenamecontrols = array();
//        $filenamecontrols[] = $mform->createElement('text', 'filenamesregex',
//            sqlrunner_str('filenamesregex'));
//        $mform->disabledIf('filenamesregex', 'attachments', 'eq', 0);
//        $mform->setType('filenamesregex', PARAM_RAW);
//        $mform->setDefault('filenamesregex', '');
//        $filenamecontrols[] = $mform->createElement('text', 'filenamesexplain',
//            sqlrunner_str('filenamesexplain'));
//        $mform->disabledIf('filenamesexplain', 'attachments', 'eq', 0);
//        $mform->setType('filenamesexplain', PARAM_RAW);
//        $mform->setDefault('filenamesexplain', '');
//        $mform->addElement('group', 'filenamesgroup',
//            sqlrunner_str('allowedfilenames'), $filenamecontrols, null, false);
//        $mform->addHelpButton('filenamesgroup', 'allowedfilenames', 'qtype_sqlrunner');
//
//        $mform->addElement('select', 'maxfilesize',
//            sqlrunner_str('maxfilesize'), $qtype->attachment_filesize_max());
//        $mform->addHelpButton('maxfilesize', 'maxfilesize', 'qtype_sqlrunner');
//                $mform->setDefault('maxfilesize', '10240');
//        $mform->disabledIf('maxfilesize', 'attachments', 'eq', 0);
    }

    public function get_data() {
        $fields = parent::get_data();
        if ($fields) {
            $fields->templateparamsevald = $this->formquestion->templateparamsevald;
        }
        return $fields;
    }

    /**
     * Add a field for a sample answer to this problem (optional)
     * @param object $mform the form being built
     */
    protected function add_sample_answer_field($mform) {
        global $CFG;
        $mform->addElement('header', 'answerhdr', sqlrunner_str('answer'), '');
        $mform->setExpanded('answerhdr', 1);

        $attributes = array(
            'rows' => 9,
            'class' => 'answer edit_code',
            'data-params' => $this->get_merged_ui_params(),
            'data-lang' => $this->acelang);
        $mform->addElement('textarea', 'answer', sqlrunner_str('answer'), $attributes);
        // Add a file attachment upload panel (disabled if attachments not allowed).
//        $options = $this->fileoptions;
//        $options['subdirs'] = false;
//        $mform->addElement('filemanager', 'sampleanswerattachments',sqlrunner_str('sampleanswerattachments'), null, $options);
//        $mform->addHelpButton('sampleanswerattachments', 'sampleanswerattachments', 'qtype_sqlrunner');
//        // Unless behat is running, hide the attachments file picker.
//        // behat barfs if it's hidden.
//        if ($CFG->prefix !== "b_") {
//            $method = method_exists($mform, 'hideIf') ? 'hideIf' : 'disabledIf';
//            $mform->$method('sampleanswerattachments', 'attachments', 'eq', 0);
//        }
        $mform->addElement('advcheckbox', 'validateonsave', null,
            sqlrunner_str('validateonsave'));
        $mform->setDefault('validateonsave', true);
        $mform->addHelpButton('answer', 'answer', 'qtype_sqlrunner');
    }

    /**
     * Add a field for a text to be preloaded into the answer box.
     * @param object $mform the form being built
     */
    protected function add_preload_answer_field($mform) {
        $mform->addElement('header', 'answerpreloadhdr', sqlrunner_str('answerpreload'), '');
        $expanded = !empty($this->formquestion->options->answerpreload);
        $mform->setExpanded('answerpreloadhdr', $expanded);
        $attributes = array(
            'rows' => 5,
            'class' => 'preloadanswer edit_code',
            'data-params' => $this->get_merged_ui_params(),
            'data-lang' => $this->acelang);
        $mform->addElement('textarea', 'answerpreload', sqlrunner_str('answerpreload'), $attributes);
        $mform->addHelpButton('answerpreload', 'answerpreload', 'qtype_sqlrunner');
    }

    /**
     * Add a field to contain extra text for use by template authors, global
     * to all tests.
     * @param object $mform the form being built
     */
    protected function add_globalextra_field($mform) {
        $mform->addElement('header', 'globalextrahdr',sqlrunner_str('globalextra'));
        $expanded = !empty($this->question->options->globalextra);
        $mform->setExpanded('globalextrahdr', $expanded);

        $attributes = array('rows' => 5,'class' => 'globalextra edit_code');
        $mform->addElement('textarea', 'globalextra',sqlrunner_str('globalextra'), $attributes);
        $mform->addHelpButton('globalextra', 'globalextra', 'qtype_sqlrunner');
    }

    /**
     * Add a set of form fields, obtained from get_per_test_fields, to the form,
     * one for each existing testcase, with some blanks for some new ones
     * This overrides the base-case version because we're dealing with test
     * cases, not answers.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $minoptions the minimum number of testcase blanks to display.
     *      Default QUESTION_NUMANS_START.
     * @param $addoptions the number of testcase blanks to add. Default QUESTION_NUMANS_ADD.
     */
    protected function add_per_testcase_fields($mform, $label, $numtestcases) {
        $mform->addElement('header', 'testcasehdr', sqlrunner_str('testcases'), '');
        $mform->setExpanded('testcasehdr', 1);
        $repeatedoptions = array();
        $repeated = $this->get_per_testcase_fields($mform, $label, $repeatedoptions);
        $this->repeat_elements($repeated, $numtestcases, $repeatedoptions,
                'numtestcases', 'addanswers', QUESTION_NUMANS_ADD,
                $this->get_more_choices_string(), true);
        $n = $numtestcases + QUESTION_NUMANS_ADD;
        for ($i = 0; $i < $n; $i++) {
            $mform->disabledIf("mark[$i]", 'allornothing', 'checked');
        }
    }

    /**
     *  A rewritten version of get_per_answer_fields specific to test cases.
     */
    protected function _create_test_cases($mform) {
        $mform->addElement('hidden', 'testcode', '');
        $mform->setType('testcode', PARAM_RAW);

        $mform->addElement('hidden', 'stdin', '');
        $mform->setType('stdin', PARAM_RAW);

        $mform->addElement('hidden', 'expected', '', array(
            'id' => 'id_expected_0'));
        $mform->setType('expected', PARAM_RAW);

        $mform->addElement('hidden', 'extra', '');
        $mform->setType('extra', PARAM_RAW);

        $mform->addElement('hidden', 'useasexample', 0);
        $mform->setType('useasexample', PARAM_INT);

//        $options = array();
//        foreach ($this->displayoptions() as $opt) {
//            $options[$opt] = sqlrunner_str($opt);
//        }

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

    /**
     *  A rewritten version of get_per_answer_fields specific to test cases.
     */
    public function get_per_testcase_fields($mform, $label, &$repeatedoptions) {
        $repeated = array();
        $repeated[] = $mform->createElement('textarea', 'testcode',
                $label,
                array('rows' => 3, 'class' => 'testcaseexpression edit_code'));
        $repeated[] = $mform->createElement('textarea', 'stdin',
                sqlrunner_str('stdin'),
                array('rows' => 3, 'class' => 'testcasestdin edit_code'));
        $repeated[] = $mform->createElement('textarea', 'expected',
                sqlrunner_str('expected'),
                array('rows' => 3, 'class' => 'testcaseresult edit_code'));

        $repeated[] = $mform->createElement('textarea', 'extra',
                sqlrunner_str('extra'),
                array('rows' => 3, 'class' => 'testcaseresult edit_code'));
        $group[] = $mform->createElement('checkbox', 'useasexample', null,
                sqlrunner_str('useasexample'));

        $options = array();
        foreach ($this->displayoptions() as $opt) {
            $options[$opt] = sqlrunner_str($opt);
        }

        $group[] = $mform->createElement('select', 'display',
                sqlrunner_str('display'), $options);
        $group[] = $mform->createElement('checkbox', 'hiderestiffail', null,
                sqlrunner_str('hiderestiffail'));
        $group[] = $mform->createElement('text', 'mark',
                sqlrunner_str('mark'),
                array('size' => 5, 'class' => 'testcasemark'));
        $group[] = $mform->createElement('text', 'ordering',
                sqlrunner_str('ordering'),
                array('size' => 3, 'class' => 'testcaseordering'));

        $repeated[] = $mform->createElement('group', 'testcasecontrols', sqlrunner_str('testcasecontrols'), $group, null, false);

        $typevalues = array(
            constants::TESTTYPE_NORMAL   => sqlrunner_str('testtype_normal'),
            constants::TESTTYPE_PRECHECK => sqlrunner_str('testtype_precheck'),
            constants::TESTTYPE_BOTH     => sqlrunner_str('testtype_both'),
        );

        $repeated[] = $mform->createElement('select', 'testtype',
                sqlrunner_str('testtype'),
                $typevalues,
                array('class' => 'testtype'));

        $repeatedoptions['expected']['type'] = PARAM_RAW;
        $repeatedoptions['testcode']['type'] = PARAM_RAW;
        $repeatedoptions['stdin']['type'] = PARAM_RAW;
        $repeatedoptions['extra']['type'] = PARAM_RAW;
        $repeatedoptions['mark']['type'] = PARAM_FLOAT;
        $repeatedoptions['ordering']['type'] = PARAM_INT;
        $repeatedoptions['testtype']['type'] = PARAM_RAW;

        foreach (array('testcode', 'stdin', 'expected', 'extra', 'testcasecontrols', 'testtype') as $field) {
            $repeatedoptions[$field]['helpbutton'] = array($field, 'qtype_sqlrunner');
        }

        // Here I expected to be able to use: $repeatedoptions['mark']['default'] = 1.000
        // but it doesn't work. See "Confusion alert" in definition_inner.

        return $repeated;
    }

    // A list of the allowed values of the DB 'display' field for each testcase.
    protected function displayoptions() {
        return array('SHOW', 'HIDE', 'HIDE_IF_FAIL', 'HIDE_IF_SUCCEED');
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
        if (isset($question->options->testcases)) { // Reloading a saved question?

            // Firstly check if we're editing a question with a missing prototype.
            // Set the broken_question message if so.
            $q = $this->make_question_from_form_data($question);
            if ($q->prototype === null) {
                $question->brokenquestionmessage = sqlrunner_str('missingprototype', array('crtype' => $question->sqlrunnertype));
            }

            // Record the prototype for subsequent use.
            $question->prototype = $q->prototype;

            // Next flatten all the question->options down into the question itself.
//            $question->testcode = array();
//            $question->expected = array();
//            $question->useasexample = array();
//            $question->display = array();
//            $question->extra = array();
//            $question->hiderestifail = array();

            foreach ($question->options->testcases as $tc) {
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
            if ($question->prototypetype != 0) {
                $question->typename = $question->sqlrunnertype;
            } else {
                $question->typename = '';
            }

            // Convert raw newline chars in testsplitterre into 2-char form
            // so they can be edited in a one-line entry field.
            if (isset($question->testsplitterre)) {
                $question->testsplitterre = str_replace("\n", '\n', $question->testsplitterre);
            }

            // Legacy questions may have a question.penalty but no penalty regime.
            // Dummy up a penalty regime from the question.penalty in such cases.
            if (empty($question->penaltyregime)) {
                if (empty($question->penalty) || $question->penalty == 0) {
                    $question->penaltyregime = '0';
                } else {
                    if (intval(100 * $question->penalty) == 100 * $question->penalty) {
                        $decdigits = 0;
                    } else {
                        $decdigits = 1;  // For nasty fractions like 0.33333333.
                    }
                    $penaltypercent = number_format($question->penalty * 100, $decdigits);
                    $penaltypercent2 = number_format($question->penalty * 200, $decdigits);
                    $question->penaltyregime = $penaltypercent . ', ' . $penaltypercent2 . ', ...';
                }
            }
        } else {
            // This is a new question.
            $question->penaltyregime = get_config('qtype_sqlrunner', 'default_penalty_regime');
        }

//        foreach (array('datafiles' => 'datafile') as $fileset => $filearea) {
//            $draftid = file_get_submitted_draft_itemid($fileset);
//            $options = $this->fileoptions;
//            $options['subdirs'] = false;
//
//            $itemid = empty($question->id) ? null :$question->id;
//
//            file_prepare_draft_area($draftid, $this->context->id, 'qtype_sqlrunner', $filearea, $itemid, $options);
//            $question->$fileset = $draftid; // File manager needs this (and we need it when saving).
//        }
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

    // Create an empty div with id id_qtype_sqlrunner_error_div for use by
    // JavaScript error handling code.
    private function make_error_div($mform) {
        $mform->addElement('html', "<div id='id_qtype_sqlrunner_error_div' class='qtype_sqlrunner_error_message'></div>");
    }

    // Add to the supplied $mform the panel "Coderunner question type".
    private function make_questiontype_panel($mform) {
//        list($languages, $types) = $this->get_languages_and_types();
//        $hidemethod = method_exists($mform, 'hideIf') ? 'hideIf' : 'disabledIf';

//        $mform->addElement('header', 'questiontypeheader', sqlrunner_str('type_header'));
        // Insert the (possible) missing prototype message as a hidden field. JavaScript
        // will be used to show it if non-empty.
        $mform->addElement('hidden', 'brokenquestionmessage', '', array('id' => 'id_broken_question', 'class' => 'brokenquestionerror'));
        $mform->setType('brokenquestionmessage', PARAM_RAW);

        // The Question Type controls (a group with just a single member).
//        $typeselectorelements = array();
//        $expandedtypes = array_merge(array('Undefined' => 'Undefined'), $types);
//        $typeselectorelements[] = $mform->createElement('select', 'sqlrunnertype', null, $expandedtypes);
        $mform->addElement('hidden', 'sqlrunnertype', static::RUNNER_TYPE);
        $mform->setType('sqlrunnertype', PARAM_RAW);

//        $mform->addElement('group', 'sqlrunner_type_group', sqlrunner_str('sqlrunnertype'), $typeselectorelements, null, false);
//        $mform->addHelpButton('sqlrunner_type_group', 'sqlrunnertype', 'qtype_sqlrunner');

        // Customisation checkboxes.
//        $typeselectorcheckboxes = array();
//        $typeselectorcheckboxes[] = $mform->createElement('advcheckbox', 'customise', null,
//                sqlrunner_str('customise'));
//        $typeselectorcheckboxes[] = $mform->createElement('advcheckbox', 'showsource', null,
//                sqlrunner_str('showsource'));
//        $mform->setDefault('showsource', false);
//        $mform->addElement('group', 'sqlrunner_type_checkboxes',
//                sqlrunner_str('questioncheckboxes'), $typeselectorcheckboxes, null, false);
//        $mform->addHelpButton('sqlrunner_type_checkboxes', 'questioncheckboxes', 'qtype_sqlrunner');

        // Answerbox controls.
//        $answerboxelements = array();
//        $answerboxelements[] = $mform->createElement('text', 'answerboxlines', sqlrunner_str('answerboxlines'),
//            array('size' => 3, 'class' => 'sqlrunner_answerbox_size'));
//        $mform->addElement('group', 'answerbox_group', sqlrunner_str('answerbox_group'), $answerboxelements, null, false);

        $mform->addElement('hidden','answerboxlines', self::DEFAULT_NUM_ROWS);
        $mform->setType('answerboxlines', PARAM_INT);
//        $mform->addHelpButton('answerbox_group', 'answerbox_group', 'qtype_sqlrunner');

        // Precheck control group (precheck + hide check).
//        $precheckelements = array();
//        $precheckvalues = array(
//            constants::PRECHECK_DISABLED => sqlrunner_str('precheck_disabled'),
//            constants::PRECHECK_EMPTY    => sqlrunner_str('precheck_empty'),
//            constants::PRECHECK_EXAMPLES => sqlrunner_str('precheck_examples'),
//            constants::PRECHECK_SELECTED => sqlrunner_str('precheck_selected'),
//            constants::PRECHECK_ALL      => sqlrunner_str('precheck_all')
//        );
//        $precheckelements[] = $mform->createElement('select', 'precheck',
//            sqlrunner_str('precheck'), $precheckvalues);
//        $precheckelements[] = $mform->createElement('advcheckbox', 'hidecheck', null,
//            sqlrunner_str('hidecheck'));
//        $mform->addElement('group', 'sqlrunner_precheck_group',
//            sqlrunner_str('submitbuttons'), $precheckelements, null, false);
//        $mform->addHelpButton('sqlrunner_precheck_group', 'precheck', 'qtype_sqlrunner');

        // Whether to show the 'Stop and read feedback' button.
//        $giveupelements = [];
//        $giveupvalues = array(
//                constants::GIVEUP_NEVER => sqlrunner_str('giveup_never'),
//                constants::GIVEUP_AFTER_MAX_MARKS => sqlrunner_str('giveup_aftermaxmarks'),
//                constants::GIVEUP_ALWAYS => sqlrunner_str('giveup_always'),
//        );

        $mform->addElement('hidden', 'hidecheck');
        $mform->setType('hidecheck', PARAM_INT);
        $mform->setDefault('hidecheck', 0);

        //$giveupelements[] = ;
        $mform->addElement('hidden', 'giveupallowed');
//        $mform->addElement('group', 'sqlrunner_giveup_group',
//            sqlrunner_str('giveup'), $giveupelements, null, false);
//        $mform->addHelpButton('sqlrunner_giveup_group', 'giveup', 'qtype_sqlrunner');
        $mform->setType('giveupallowed', PARAM_INT);
        $mform->setDefault('giveupallowed', constants::GIVEUP_ALWAYS);

        // Feedback control (a group with only one element).
//        $feedbackelements = array();
//        $feedbackvalues = array(
//            constants::FEEDBACK_USE_QUIZ => sqlrunner_str('feedback_quiz'),
//            constants::FEEDBACK_SHOW    => sqlrunner_str('feedback_show'),
//            constants::FEEDBACK_HIDE => sqlrunner_str('feedback_hide'),
//        );

        //$feedbackelements[] = ;
        $mform->addElement('hidden', 'displayfeedback');
//        $mform->addElement('group', 'sqlrunner_feedback_group', sqlrunner_str('feedback'), $feedbackelements, null, false);
//        $mform->addHelpButton('sqlrunner_feedback_group', 'feedback', 'qtype_sqlrunner');
        $mform->setDefault('displayfeedback', constants::FEEDBACK_SHOW);
        $mform->setType('displayfeedback', PARAM_INT);

        // Marking controls.
//        $markingelements = array();
//        $markingelements[] = $mform->createElement('advcheckbox', 'allornothing', null, sqlrunner_str('allornothing'));
//        $markingelements[] = $mform->createElement('text', 'penaltyregime', sqlrunner_str('penaltyregimelabel'), array('size' => 20));
//        $mform->addElement('group', 'markinggroup', sqlrunner_str('markinggroup'), $markingelements, null, false);

        $mform->createElement('hidden', 'allornothing', true);
        $mform->createElement('hidden', 'penaltyregime', $this->_penalty);

        $mform->setType('allornothing', PARAM_BOOL);
        $mform->setType('penaltyregime', PARAM_RAW);

        // Template params.
//        $mform->addElement('textarea', 'templateparams',
//            sqlrunner_str('templateparams'),
//            array('rows' => self::TEMPLATE_PARAM_ROWS,
//                  'class' => 'edit_code',
//                  'data-lang' => '' // Don't syntax colour template params.
//            )
//        );
//        $mform->setType('templateparams', PARAM_RAW);
//        $mform->addHelpButton('templateparams', 'templateparams', 'qtype_sqlrunner');

        // Twig controls.
//        $twigelements = array();
//        $twigelements[] = $mform->createElement('advcheckbox', 'hoisttemplateparams', null,
//            sqlrunner_str('hoisttemplateparams'));
//        $twigelements[] = $mform->createElement('advcheckbox', 'twigall', null,
//            sqlrunner_str('twigall'));
//        $templateparamlangs = array(
//            'None'    => 'None',
//            'twig'    => 'Twig',
//            'python3' => 'Python3',
//            'c'       => 'C',
//            'cpp'     => 'C++',
//            'java'    => 'Java',
//            'php'     => 'php',
//            'octave'  => 'Octave',
//            'pascal'  => 'Pascal',
//        );
//        $twigelements[] = $mform->createElement('select', 'templateparamslang',
//            sqlrunner_str('templateparamslang'), $templateparamlangs);
//        $twigelements[] = $mform->createElement('advcheckbox', 'templateparamsevalpertry', null,
//            sqlrunner_str('templateparamsevalpertry'));
//        $mform->addElement('group', 'twigcontrols', sqlrunner_str('twigcontrols'),
//                $twigelements, null, false);
//        $mform->setDefault('templateparamslang', 'None');
//        $mform->setDefault('templateparamsevalpertry', false);
//        $mform->setDefault('twigall', false);
//        $mform->$hidemethod('templateparamsevalpertry', 'templateparamslang', 'eq', 'None');
//        $mform->$hidemethod('templateparamsevalpertry', 'templateparamslang', 'eq', 'twig');
//        $mform->setDefault('hoisttemplateparams', true);
//        $mform->addHelpButton('twigcontrols', 'twigcontrols', 'qtype_sqlrunner');

        // UI parameters.
//        $uiplugin = empty($this->question->options->uiplugin) ? 'none' : $this->question->options->uiplugin;
//        $plugins = qtype_sqlrunner_ui_plugins::get_instance();
//        $pluginswithoutparams = $plugins->all_with_no_params();

//        $uielements = array();
//        $uiparamedescriptionhtml = '<div class="ui_parameters_descr"></div>'; // JavaScript fills this.
//        $uielements[] = $mform->createElement('html', $uiparamedescriptionhtml);
//        $uielements[] = $mform->createElement('hidden', 'uiparameters',
//            sqlrunner_str('uiparameters'),
//            array('rows' => self::UI_PARAM_ROWS,
//                  'class' => 'edit_code',
//                  'data-lang' => '' // Don't syntax colour ui params.
//            )
//        );
        // id needed for js
        $mform->addElement('hidden', 'uiparameters', '', array('id' => 'id_uiparameters'));
        $mform->setType('uiparameters', PARAM_RAW);
//        $mform->addElement('group', 'uiparametergroup', sqlrunner_str('uiparametergroup'), $uielements, null, false);
//        $mform->addHelpButton('uiparametergroup', 'uiparametergroup', 'qtype_sqlrunner');
    }

    // Add to the supplied $mform the question-type help panel.
    // This displays the text of the currently-selected prototype.
    private function make_questiontype_help_panel($mform) {
//        $mform->addElement('header', 'questiontypehelpheader',
//            sqlrunner_str('questiontypedetails'));
//        $nodetailsavailable = '<span id="qtype-help">' . sqlrunner_str('nodetailsavailable') . '</span>';
//        $mform->addElement('html', $nodetailsavailable);
    }

    // Add to the supplied $mform the Customisation Panel
    // The panel is hidden by default but exposed when the user clicks
    // the 'Customise' checkbox in the question-type panel.
    private function make_customisation_panel($mform) {
        // The following fields are used to customise a question by overriding
        // values from the base question type. All are hidden
        // unless the 'customise' checkbox is checked.

        $mform->addElement('header', 'customisationheader',
            sqlrunner_str('customisation'));
//        $attributes = array(
//            'rows'      => 8,
//            'class'     => 'template edit_code',
//            'name'      => 'template',
//            'data-lang' => $this->lang,
//        );
        $mform->addElement('hidden', 'template');
        $mform->setType('template', PARAM_TEXT);
        $mform->setDefault('template', '{{STUDENT_ANSWER}}');

        $templatecontrols = array();
        $templatecontrols[] = $mform->createElement('advcheckbox', 'iscombinatortemplate', null, sqlrunner_str('iscombinatortemplate'));
        $templatecontrols[] = $mform->createElement('advcheckbox', 'allowmultiplestdins', null, sqlrunner_str('allowmultiplestdins'));

        $mform->setDefault('iscombinatortemplate', 1);

        $templatecontrols[] = $mform->createElement('text', 'testsplitterre', sqlrunner_str('testsplitterre'), array('size' => 45));
        $mform->setType('testsplitterre', PARAM_RAW);

        $mform->addElement('group', 'templatecontrols', sqlrunner_str('templatecontrols'), $templatecontrols, null, false);
        $mform->addHelpButton('templatecontrols', 'templatecontrols', 'qtype_sqlrunner');

        $gradingcontrols = array();
        $gradertypes = array(
            'SqlEqualityGrader'  => sqlrunner_str('sqlequalitygrader'),
            'EqualityGrader'     => sqlrunner_str('equalitygrader'),
            'NearEqualityGrader' => sqlrunner_str('nearequalitygrader'),
            'RegexGrader'        => sqlrunner_str('regexgrader'),
            'TemplateGrader'     => sqlrunner_str('templategrader'),
        );
        $gradingcontrols[] = $mform->createElement('select', 'grader', null, $gradertypes);
        $mform->addElement('group', 'gradingcontrols', sqlrunner_str('grading'), $gradingcontrols, null, false);
        $mform->addHelpButton('gradingcontrols', 'gradingcontrols', 'qtype_sqlrunner');
        $mform->setDefault('gradingcontrols', 'SqlEqualityGrader');

        $mform->addElement('text', 'resultcolumns', sqlrunner_str('resultcolumns'), array('size' => self::RESULT_COLUMNS_SIZE));
        $mform->setType('resultcolumns', PARAM_RAW);
        $mform->addHelpButton('resultcolumns', 'resultcolumns', 'qtype_sqlrunner');

        $uicontrols = array();
        $plugins = qtype_sqlrunner_ui_plugins::get_instance();
        $uitypes = $plugins->dropdownlist();

        $uicontrols[] = $mform->createElement('select', 'uiplugin', sqlrunner_str('student_answer'), $uitypes);
        $mform->setDefault('uiplugin', 'ace');
        $uicontrols[] = $mform->createElement('advcheckbox', 'useace', null, sqlrunner_str('useace'));
        $mform->setDefault('useace', true);
        $mform->addElement('group', 'uicontrols', sqlrunner_str('uicontrols'), $uicontrols, null, false);
        $mform->addHelpButton('uicontrols', 'uicontrols', 'qtype_sqlrunner');

        $attributes = array(
            'rows' => 5,
            'class' => 'prototypeextra edit_code');
        $mform->addElement('textarea', 'prototypeextra',
            sqlrunner_str('prototypeextra'),
                $attributes);
        $mform->addHelpButton('prototypeextra', 'prototypeextra', 'qtype_sqlrunner');

        $mform->setExpanded('customisationheader');  // Although expanded it's hidden until JavaScript unhides it .
    }

    // Make the advanced customisation panel, also hidden until the user
    // customises the question. The fields in this part of the form are much more
    // advanced and not recommended for most users.
    private function make_advanced_customisation_panel($mform) {
        $mform->addElement('header', 'advancedcustomisationheader', sqlrunner_str('advanced_customisation'));

        $prototypecontrols = array();
        $prototypeselect = $mform->createElement('select', 'prototypetype', sqlrunner_str('prototypeQ'));
        $prototypeselect->addOption('No', '0');
        $prototypeselect->addOption('Yes (built-in)', '1', array('disabled' => 'disabled'));
        $prototypeselect->addOption('Yes (user defined)', '2');

        $prototypecontrols[] = $prototypeselect;
        $prototypecontrols[] = $mform->createElement('text', 'typename', sqlrunner_str('typename'), array('size' => 30));
        $mform->addElement('group', 'prototypecontrols', sqlrunner_str('prototypecontrols'), $prototypecontrols, null, false);

        $mform->setType('typename', PARAM_RAW_TRIMMED);
        $mform->addElement('hidden', 'saved_prototype_type');
        $mform->setType('saved_prototype_type', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('prototypecontrols', 'prototypecontrols', 'qtype_sqlrunner');

        $sandboxcontrols = array();

        $enabled = qtype_sqlrunner_sandbox::enabled_sandboxes();
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

        $sandboxcontrols[] = $mform->createElement('text', 'cputimelimitsecs',
            sqlrunner_str('cputime'), array('size' => 3));
        $sandboxcontrols[] = $mform->createElement('text', 'memlimitmb',
            sqlrunner_str('memorylimit'), array('size' => 5));
        $sandboxcontrols[] = $mform->createElement('text', 'sandboxparams',
            sqlrunner_str('sandboxparams'), array('size' => 15));
        $mform->addElement('group', 'sandboxcontrols',
            sqlrunner_str('sandboxcontrols'),
                $sandboxcontrols, null, false);

        $mform->setType('cputimelimitsecs', PARAM_RAW);
        $mform->setType('memlimitmb', PARAM_RAW);
        $mform->setType('sandboxparams', PARAM_RAW);
        $mform->addHelpButton('sandboxcontrols', 'sandboxcontrols', 'qtype_sqlrunner');

        //$languages = array();
        //$languages[]  =

        $mform->addElement('hidden', 'language');
        $mform->setType('language', PARAM_RAW_TRIMMED);
        $mform->setDefault('language', static::RUNNER_TYPE);

//        $mform->createElement('text', 'language', sqlrunner_str('language'), array('size' => 10));
//        $mform->setType('language', PARAM_RAW_TRIMMED);

        //$languages[]  =
        $mform->addElement('hidden', 'acelang');
        $mform->setType('acelang', PARAM_RAW_TRIMMED);
        $mform->setDefault('acelang', static::RUNNER_TYPE);

//        $mform->addElement('group', 'languages', sqlrunner_str('languages'), $languages, null, false);
//        $mform->addHelpButton('languages', 'languages', 'qtype_sqlrunner');

        // IMPORTANT: authorform.js has to set the initial enabled/disabled
        // status of the testsplitterre and allowmultiplestdins elements
        // after loading a new question type as the following code apparently
        // sets up event handlers only for clicks on the iscombinatortemplate
        // checkbox.
        $mform->disabledIf('typename', 'prototypetype', 'neq', '2');
        $mform->disabledIf('testsplitterre', 'iscombinatortemplate', 'eq', 0);
        $mform->disabledIf('allowmultiplestdins', 'iscombinatortemplate', 'eq', 0);
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
        if ($data['sqlrunnertype'] == 'Undefined') {
            $errors['sqlrunner_type_group'] = sqlrunner_str('questiontype_required');
            return $errors;  // Don't continue checking in this case. Template param validation breaks.
        }
        if ($data['cputimelimitsecs'] != '' &&
             (!ctype_digit($data['cputimelimitsecs']) || intval($data['cputimelimitsecs']) <= 0)) {
            $errors['sandboxcontrols'] = sqlrunner_str('badcputime');
        }
        if ($data['memlimitmb'] != '' &&
             (!ctype_digit($data['memlimitmb']) || intval($data['memlimitmb']) < 0)) {
            $errors['sandboxcontrols'] = sqlrunner_str('badmemlimit');
        }

//        if ($data['precheck'] == constants::PRECHECK_EXAMPLES && $this->num_examples($data) === 0) {
//            $errors['sqlrunner_precheck_group'] = sqlrunner_str('precheckingemptyset');
//        }

        if ($data['sandboxparams'] != '' &&
                json_decode($data['sandboxparams']) === null) {
            $errors['sandboxcontrols'] = sqlrunner_str('badsandboxparams');
        }

        list($templateerrors, $json) = $this->validate_template_params();
        if (!$templateerrors) {
            $this->formquestion->templateparamsevald = $json;
            $this->formquestion->parameters = json_decode($json, true);
        } else {
            $errors['templateparams'] = $templateerrors;
            $this->formquestion->templateparamsevald = '{}';
        }

        if (!$templateerrors && isset($data['uiparameters']) && $data['uiparameters']) {
            $uiparametererrors = $this->validate_ui_parameters($data['uiparameters']);
            if ($uiparametererrors) {
                $errors['uiparametergroup'] = $uiparametererrors;
            }
        }

        if ($data['prototypetype'] == 0 && ($data['grader'] !== 'TemplateGrader' || $data['iscombinatortemplate'] === false)) {
            // Unless it's a prototype or uses a combinator-template grader,
            // it needs at least one testcase.
            $testcaseerrors = $this->validate_test_cases($data);
            $errors = array_merge($errors, $testcaseerrors);
        }

//        if ($data['iscombinatortemplate'] && empty($data['testsplitterre'])) {
//            $errors['templatecontrols'] = sqlrunner_str('bad_empty_splitter');
//        }

        if ($data['prototypetype'] == 2 && ($data['saved_prototype_type'] != 2 ||
                   $data['typename'] != $data['sqlrunnertype'])) {
            // User-defined prototype, either newly created or undergoing a name change.
            $typename = trim($data['typename']);
            if ($typename === '') {
                $errors['prototypecontrols'] = sqlrunner_str('empty_new_prototype_name');
            } else if (!$this->is_valid_new_type($typename)) {
                $errors['prototypecontrols'] = sqlrunner_str('bad_new_prototype_name');
            }
        }

        $penaltyregimeerror = $this->validate_penalty_regime($data);
        if ($penaltyregimeerror) {
             $errors['markinggroup'] = $penaltyregimeerror;
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

//        if ($data['attachments']) {
//            // Check a valid regular expression was given.
//            // Use '=' as the PCRE delimiter.
//            if (@preg_match('=^' . $data['filenamesregex'] . '$=', null) === false) {
//                $errors['filenamesgroup'] = sqlrunner_str('badfilenamesregex');
//            }
//        }

//        if (count($errors) == 0 && $data['twigall']) {
//            $errors = $this->validate_twigables();
//        }

        if (count($errors) == 0 && !empty($data['validateonsave'])) {
            $testresult = $this->validate_sample_answer($data);
            if ($testresult) {
                $errors['answer'] = $testresult;
            }
        }

        $acelangs = trim($data['acelang']);
        if ($acelangs !== '' && strpos($acelangs, ',') !== false) {
            $parsedlangs = qtype_sqlrunner_util::extract_languages($acelangs);
            if ($parsedlangs === false) {
                $errors['languages'] = sqlrunner_str('multipledefaults');
            } else if (count($parsedlangs[0]) === 0) {
                $errors['languages'] = sqlrunner_str('badacelangstring');
            }
        }

        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
//        if ($data['attachments'] != -1 && $data['attachments'] < $data['attachmentsrequired'] ) {
//            $errors['attachmentsrequired']  = sqlrunner_str('mustrequirefewer');
//        }

        return $errors;
    }

    // Check the templateparameters value, if given. Return an array containing
    // the error message string, which will be empty if there are no errors,
    // and the JSON evaluated template parameters, which will be empty if there
    // are errors.
    private function validate_template_params() {
        global $USER;
//        $templateparams = $this->formquestion->templateparams;
        $errormessage = '';
        $json = '';
        $seed = mt_rand();  // TODO use a fixed seed if !evaluate_per_try.
        try {
            $json = $this->formquestion->evaluate_merged_parameters($seed);
            $decoded = json_decode($json, true);
            if ($decoded === null) {
                $errormessage = sqlrunner_str('badtemplateparams', $json);
            }
        } catch (qtype_sqlrunner_bad_json_exception $e) {
            $errormessage = sqlrunner_str('badtemplateparams', $e->getMessage());
        } catch (Exception $e) {
            $errormessage = sqlrunner_str('badtemplateparams','** Unknown error **');
        }

        if ($errormessage === '') {
            // Check for legacy case of ui parameters defined within the template params.
            $uiplugin = $this->formquestion->uiplugin;
            $uiparams = new qtype_sqlrunner_ui_parameters($uiplugin);
            $templateparamsnoprototype = json_decode($this->formquestion->template_params_json($seed), true);
            $alluiparamnames = $uiparams->all_names();
            $badparams = array();
            foreach (array_keys($templateparamsnoprototype) as $paramname) {
                if (in_array($paramname, $alluiparamnames)) {
                    $badparams[] = $paramname;
                }
            }
            if ($badparams) {
                $errormessage = sqlrunner_str('legacyuiparams') . implode(', ', $badparams);
            } else {
                foreach (array_keys($templateparamsnoprototype) as $paramname) {
                    // Also check if  template parameter starts with UI plugin name and
                    // an underscore followed by a valid ui parameter name.
                    $bits = explode('_', $paramname, 2);
                    if (count($bits) > 1 && $bits[0] === $uiplugin && in_array($bits[1], $alluiparamnames)) {
                        $badparams[] = $paramname;
                    }
                    if ($badparams) {
                        $extra = array('uiname' => $uiplugin);
                        $errormessage = sqlrunner_str('legacyuiparams2', $extra) . implode(', ', $badparams);
                    }
                }
            }
        }

        return array($errormessage, $json);
    }

    // Check that the uiparameters field, if present and non-empty, is valid.
    // Return an error message string if not valid, else an empty string.
    private function validate_ui_parameters($uiparameters) {
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
               $errormessage = sqlrunner_str('baduiparams');
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
                $errormessage = sqlrunner_str('illegaluiparamname', array('uiname' => $uipluginname)) . implode(', ', $badparams);
            } else if ($checkmissing) {
                // Make sure any required ui parameters are defined.
                $missingparams = array();
                foreach ($alluiparamnames as $uiname) {
                    if ($uiparams->is_required($uiname) && !in_array($uiname, array_keys($decoded))) {
                        $missingparams[] = $uiname;
                    }
                }
                if ($missingparams) {
                    $errormessage = sqlrunner_str('missinguiparams') . implode(', ', $missingparams);
                }
            }
        }

        return $errormessage;
    }

    private function validate_penalty_regime($data) {
        // Check the penalty regime and return an error string or an empty string if OK.
        $errorstring = '';
        $expectedpr = '/[0-9]+(\.[0-9]*)?%?([, ] *[0-9]+(\.[0-9]*)?%?)*([, ] *...)?/';
        $penaltyregime = trim($data['penaltyregime']);
        if ($penaltyregime == '') {
            $errorstring = sqlrunner_str('emptypenaltyregime');
        } else if (!preg_match($expectedpr, $penaltyregime)) {
            $errorstring = sqlrunner_str('badpenalties');
        } else {
            $penaltyregime = str_replace('%', '', $penaltyregime);
            $penaltyregime = str_replace(',', ', ', $penaltyregime);
            $penaltyregime = preg_replace('/ *,? +/', ', ', $penaltyregime);
            $bits = explode(', ', $penaltyregime);
            $n = count($bits);
            if ($bits[$n - 1] === '...') {
                if ($n < 3 || floatval($bits[$n - 2]) <= floatval($bits[$n - 3])) {
                    // If it ends with '...', ensure the last two numbers are in increasing order.
                    $errorstring = sqlrunner_str('bad_dotdotdot');
                }
                $n--;
            }
            if ($errorstring === '') {
                // Check all elements are valid numbers.
                for ($i = 0; $i < $n; $i++) {
                    if (!is_numeric($bits[$i])) {
                        $errorstring = sqlrunner_str('badpenalties');
                        break;
                    }
                }
            }
        }
        return $errorstring;
    }

    // Check for twig errors in all fields except the template itself, which
    // is checked when the answer is validated. Checking it here would require
    // setting up a runtime context with STUDENT_ANSWER and TEST or TESTCASES etc.
    // Return value is an associative array mapping from
    // form fields to error messages.
    // Should only be called if twig all is set.
    private function validate_twigables() {
        $errors = array();
        $question = $this->formquestion;
        $jsonparams = $question->templateparamsevald;
        $parameters = json_decode($jsonparams, true);
        $parameters['QUESTION'] = $question;

        // Try twig expanding everything (see question::twig_all), with strict_variables true.
        foreach (['questiontext', 'answer', 'answerpreload', 'globalextra'] as $field) {
            $text = $question->$field;
            if (is_array($text)) {
                $text = $text['text'];
            }
            try {
                $this->twig_render($text, $parameters, true);
            } catch (Exception $ex) {
                $errors[$field] = sqlrunner_str('twigerror', $ex->getMessage());
            }
        }

        // Now all test cases.
        if (!empty($question->testcode)) {
            $num = max(count($question->testcode), count($question->stdin),
                    count($question->expected), count($question->extra));

            foreach (['testcode', 'stdin', 'expected', 'extra'] as $fieldname) {
                $fields = $question->$fieldname;
                for ($i = 0; $i < $num; $i++) {
                    $text = $fields[$i];
                    try {
                        $this->twig_render($text, $parameters, true);
                    } catch (Exception $ex) {
                        $errors["testcode[$i]"] = sqlrunner_str('twigerrorintest', $ex->getMessage());
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Validate the test cases.
     *
     * Base validation @see static::_default_validate_test_cases()
     */
    private function validate_test_cases($data) {
        $errors = array(); // Return value.
        $testcodes = [$data['answer']];
        $stdins = [''];
        $expecteds = [''];
        $marks = [1];
        $count = $numnonemptytests = 0;
        $num = 1; // set only one test case that equals 'answer' field
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
                        $errors["testcode[$i]"] = sqlrunner_str('nonnumericmark');
                    } else if (floatval($mark) <= 0) {
                        $errors["testcode[$i]"] = sqlrunner_str('negativeorzeromark');
                    }
                }
            }
        }

        if ($count == 0) {
            $errors["testcode[0]"] = sqlrunner_str('atleastonetest');
        } else if ($numnonemptytests != 0 && $numnonemptytests != $count) {
            $errors["testcode[0]"] = sqlrunner_str('allornone');
        }
        return $errors;
    }

    /**
     * CodeRunner test case Validation.
     */
    private function _default_validate_test_cases($data) {
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
                        $errors["testcode[$i]"] = sqlrunner_str('nonnumericmark');
                    } else if (floatval($mark) <= 0) {
                        $errors["testcode[$i]"] = sqlrunner_str('negativeorzeromark');
                    }
                }
            }
        }

        if ($count == 0) {
            $errors["testcode[0]"] = sqlrunner_str('atleastonetest');
        } else if ($numnonemptytests != 0 && $numnonemptytests != $count) {
            $errors["testcode[0]"] = sqlrunner_str('allornone');
        }
        return $errors;
    }

    // Check the sample answer (if there is one).
    // Return an empty string if there is no sample answer and no attachments,
    // or if the sample answer passes all the tests.
    // Otherwise return a suitable error message for display in the form.
    private function validate_sample_answer() {
        //$attachmentssaver = $this->get_sample_answer_file_saver();
        $files = [];//$attachmentssaver ? $attachmentssaver->get_files() : array();
        $answer = $this->formquestion->answer;
        if (trim($answer) === '' && count($files) == 0) {
            return ''; // Empty answer and no attachments.
        }
        // Check if it's a multilanguage question; if so need to determine
        // what language to use. If there is a specific answer_language template
        // parameter, that is used. Otherwise the default language (if specified)
        // or the first in the list is used.
        $acelang = trim($this->formquestion->acelang);
        if ($acelang !== '' && strpos($acelang, ',') !== false) {
            if (empty($this->formquestion->parameters['answer_language'])) {
                list($languages, $answerlang) = qtype_sqlrunner_util::extract_languages($acelang);
                if ($answerlang === '') {
                    $answerlang = $languages[0];
                }
            } else {
                $answerlang = $this->formquestion->parameters['answer_language'];
            }
        }

        try {
            $savedevalpertry = $this->formquestion->templateparamsevalpertry ?? 0;
            if (!isset($this->formquestion->uiparameters)) {
                $this->formquestion->uiparameters = null; // If hidden, value isn't recorded in formquestion.
            }
            $this->formquestion->templateparamsevalpertry = 0; // Save an extra evaluation.
            $this->formquestion->start_attempt();
            $this->formquestion->templateparamsevalpertry = $savedevalpertry;
            $response = array('answer' => $this->formquestion->answer);
            if (!empty($answerlang)) {
                $response['language'] = $answerlang;
            }
//            if ($attachmentssaver) {
//                $response['attachments'] = $attachmentssaver;
//            }
            $error = $this->formquestion->validate_response($response);
            if ($error) {
                return $error;
            }
            list($mark, $state, $cachedata) = $this->formquestion->grade_response($response, false, true);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        // Return either an empty string if run was good or an error message.
        if ($mark == 1.0) {
            return '';
        } else {
            $outcome = unserialize($cachedata['_testoutcome']);
            if(!empty($cachedata['_runneroutput']) && !empty($outcome->testresults)){
                // get student output to another step, because test_outcome is serialised and big strings are snip.
                // also sql_result can be encoded and contains non-ASCII symbols, which already brokes while serialise
                $outcome->testresults[0]->got = $cachedata['_runneroutput'];
            }
            $error = $outcome->validation_error_message();
            return $error;
        }
    }

    // UTILITY FUNCTIONS.
    // =================.

    // True iff the given name is valid for a new type, i.e., it's not in use
    // in the current context (Currently only a single global context is
    // implemented).
    private function is_valid_new_type($typename) {
        list($langs, $types) = $this->get_languages_and_types();
        return !array_key_exists($typename, $types);
    }

    /**
     * Return a count of the number of test cases set as examples.
     * @param array $data data from the form
     */
    private function num_examples($data) {
        return isset($data['useasexample']) ? count($data['useasexample']) : 0;
    }

    private function get_languages_and_types() {
        // Return two arrays (language => language_upper_case) and (type => subtype) of
        // all the sqlrunner question types available in the current course
        // context.
        // The subtype is the suffix of the type in the database,
        // e.g. for java_method it is 'method'. The language is the bit before
        // the underscore, and language_upper_case is a capitalised version,
        // e.g. Java for java. For question types without a
        // subtype the word 'Default' is used.

        global $COURSE;
        $courseid = $COURSE->id;
        $records = qtype_sqlrunner::get_all_prototypes($courseid);
        $types = array();
        foreach ($records as $row) {
            if (($pos = strpos($row->sqlrunnertype, '_')) !== false) {
                $subtype = substr($row->sqlrunnertype, $pos + 1);
                $language = substr($row->sqlrunnertype, 0, $pos);
            } else {
                $subtype = 'Default';
                $language = $row->sqlrunnertype;
            }
            $types[$row->sqlrunnertype] = $row->sqlrunnertype;
            $languages[$language] = ucwords($language);
        }
        asort($types);
        asort($languages);
        return array($languages, $types);
    }

    // Render the given Twig text with the given params, using the global
    // $USER variable (the question author) as a dummy student.
    // @return Rendered text.
    private function twig_render($text, $params=array(), $isstrict=false) {
        global $USER;
        $student = new qtype_sqlrunner_student($USER);
        return qtype_sqlrunner_twig::render($text, $student, (array) $params, $isstrict);
    }

    private function make_question_from_form_data($data) {
        // Construct a question object containing all the fields from $data.
        // Used in data pre-processing and when validating a question.
        global $DB, $USER;
        $question = new qtype_sqlrunner_question();
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
//        $question->supportfilemanagerdraftid = $this->get_file_manager('datafiles');
        $question->student = new qtype_sqlrunner_student($USER);
        $question->iscombinatortemplate = 1;

        // Clean the question object, get inherited fields.
        $qtype = new qtype_sqlrunner();
        $qtype->clean_question_form($question, true);
        $questiontype = $question->sqlrunnertype;
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
    private function get_merged_ui_params() {
        global $USER;
        if (isset($this->cacheduiparamsjson)) {
            return $this->cacheduiparamsjson;
        }
        $q = $this->question;
        if (isset($q->options)) {
            // Editing an existing question.
            try {
                $qfromdb = question_bank::load_question($q->id);
                $qfromdb->student = new qtype_sqlrunner_student($USER);
                $seed = 1;
                $qfromdb->evaluate_question_for_display($seed, null);
                if ($qfromdb->mergeduiparameters) {
                    $json = json_encode($qfromdb->mergeduiparameters);
                } else {
                    $json = '{}';
                }
            } catch (Throwable $e) {
                $json = '{}';  // This shouldn't happen, but has been known to.
                $q->brokenquestionmessage = sqlrunner_str('corruptuiparams');
            };
            $this->cacheduiparamsjson = $json;
            return $json;
        } else {
            return '{}';
        }
    }

    // Return a file saver for the sample answer filemanager, if present.
    private function get_sample_answer_file_saver() {
        $sampleanswerdraftid = $this->get_file_manager('sampleanswerattachments');
        $saver = null;
        if ($sampleanswerdraftid) {
            $saver = new question_file_saver($sampleanswerdraftid, 'qtype_sqlrunner', 'draft');
        }
        return $saver;
    }


    // Find the id of the filemanager element draftid with the given name.
    private function get_file_manager($filemanagername){
        $mform = $this->_form;
        $index = $mform->_elementIndex[$filemanagername];
        $element = $mform->_elements[$index];
        if ($element->_type == 'filemanager' && $element->_attributes['name'] === $filemanagername){
            return (int)$element->getValue();
        }

        return null;
    }
}
