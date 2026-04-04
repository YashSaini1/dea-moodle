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
 * Defines the editing form for the data_modeling question type.
 *
 * @package    qtype
 * @subpackage data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/edit_question_form.php');
require_once($CFG->dirroot.'/question/type/data_modeling/questiontype.php');
require_once($CFG->dirroot.'/question/type/sqlrunner/lib.php');

use core_question\local\bank\question_version_status;
use qbank_managecategories\helper;

/**
 * data_modeling editing form definition.
 *
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class qtype_data_modeling_edit_form extends question_edit_form {
    protected $ap = null;

    public function qtype(){
        return 'data_modeling';
    }

    protected function definition(){
        global $PAGE;
        $this->parent_definition();
        qtype_sqlrunner_util::load_ace();

        $PAGE->requires->js_call_amd('qtype_sqlrunner/textareas', 'setupAllTAs');
        $PAGE->requires->js_call_amd('qtype_data_modeling/authorform', 'initEditForm');
        $PAGE->add_body_class('admin_editor_page');
    }

    protected function parent_definition(){
        $mform = $this->_form;

        // Standard fields at the start of the form.
        $mform->addElement('header', 'generalheader', get_string('general', 'form'));
        $system_ctx = \context_system::instance();
        if (!isset($this->question->id)){
            if (!empty($this->question->formoptions->mustbeusable)){
                $contexts = $this->contexts->having_add_and_use();
            } else {
                $contexts = $this->contexts->having_cap('moodle/question:add');
            }
            if (\core\plugininfo\qbank::is_plugin_enabled(helper::PLUGINNAME)){
                // we need to get default system category as default
                // If user not change it - trigger validation message
                /** @noinspection PhpParamsInspection There is a mistake in moodle documentation */
                $sys_cat = helper::get_categories_for_contexts($system_ctx->id, 'sortorder, id', false);
                $sys_cat = reset($sys_cat);
                // default system category value
                $sys_cat_value = $sys_cat->id.','.$sys_cat->contextid;
                $categories = [
                    $sys_cat_value => sqlrunner_str('not_selected'),
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
                if (empty($ctx_cats)){
                    throw new moodle_exception('exception_emptycategory');
                }

                $options_list_params = array(
                    'placeholder' => sqlrunner_str('select_category'),
                );
                $categories = array_merge($categories, $ctx_cats);

                $mform->addElement('select', 'category', get_string('category', 'question'),
                    $categories, $options_list_params);
                $mform->addRule('category', 'required', 'required', null, 'client');

                $mform->setDefault('category', $sys_cat_value);
            } else {
                // Adding question.
                $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $contexts));
            }
        } elseif (!($this->question->formoptions->canmove || $this->question->formoptions->cansaveasnew)) {
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
            if (isset($this->question->beingcopied)){
                $beingcopied = $this->question->beingcopied;
            }
            if (($this->question->formoptions->canedit ||
                    $this->question->formoptions->cansaveasnew) && ($beingcopied)){
                // Not move only form.
                $currentgrp[1] = $mform->createElement('checkbox', 'usecurrentcat', '',
                    get_string('categorycurrentuse', 'question'));
                $mform->setDefault('usecurrentcat', 1);
            }
            $currentgrp[0]->freeze();
            $currentgrp[0]->setPersistantFreeze(false);
            $mform->addGroup($currentgrp, 'currentgrp',
                get_string('categorycurrent', 'question'), null, false);

            if (($beingcopied)){
                $mform->addElement('questioncategory', 'categorymoveto',
                    get_string('categorymoveto', 'question'),
                    array('contexts' => array($this->categorycontext)));
                if ($this->question->formoptions->canedit ||
                    $this->question->formoptions->cansaveasnew){
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
        $mform->setType('status', PARAM_TEXT);
        $mform->setDefault('status', question_version_status::QUESTION_STATUS_READY);

        $mform->addElement('hidden', 'defaultmark');
        $mform->setType('defaultmark', PARAM_FLOAT);
        $mform->setDefault('defaultmark', 1);
//        $mform->addRule('defaultmark', null, 'required', null, 'client');

        $mform->addElement('editor', 'generalfeedback', get_string('see_solution_text', DATA_MODELING),
            array('rows' => 10), $this->editoroptions);
        $mform->setType('generalfeedback', PARAM_RAW);
//        $mform->addHelpButton('generalfeedback', 'generalfeedback', 'question');

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
            && \core\plugininfo\qbank::is_plugin_enabled('qbank_tagquestion')){
            $this->add_tag_fields($mform);
        }

        if ($this->customfieldpluginenabled){
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
                $this->question->formoptions->cansaveasnew))){
            $mform->hardFreezeAllVisibleExcept(array('categorymoveto', 'buttonar', 'currentgrp'));
        }
    }

    protected function definition_inner($mform){
        $this->add_sample_answer_field($mform);
    }

    /**
     * Add a field for a sample answer to this problem (optional)
     * @param MoodleQuickForm $mform the form being built
     */
    protected function add_sample_answer_field($mform) {
        global $PAGE;
        $mform->addElement('header', 'answerhdr', sqlrunner_str('answer'), '');
        $mform->setExpanded('answerhdr', 1);

        $attributes = array(
            'rows' => 9,
            'class' => 'answer edit_code',
            'data-params' => '{}',
            'data-lang' => 'dbml');
        $mform->addElement('textarea', 'answer', sqlrunner_str('answer'), $attributes);
        $mform->addElement('html', '<div id="data_modelling_editor" page="admin"></div>');

        $mform->addElement('hidden', 'table_code', '', array('id' => 'data_modeling_table_code'));
        $mform->setType('table_code', PARAM_RAW);

        $PAGE->requires->js_call_amd('qtype_data_modeling/app-lazy');
    }

    protected function data_preprocessing($question){
        $question = parent::data_preprocessing($question);
        return $question;
    }

    public function set_data($question){
        parent::set_data($question);
    }

    public function validation($data, $files){
        [$catid, $ctx] = explode(',', $data['category']);
        if ($ctx == \context_system::instance()->id){
            return ['category' => 'Category field is required'];
        }
        $errors = parent::validation($data, $files);
//        $errors = $this->validate_answers($data, $errors);
//        $errors = $this->validate_data_modeling_options($data, $errors);
        return $errors;
    }

    /**
     * Validate the answers.
     *
     * @param array $data   the submitted data.
     * @param array $errors the errors array to add to.
     *
     * @return array the updated errors array.
     */
    protected function validate_answers($data, $errors){

    }

    /**
     * @return string erre describing what an answer should be.
     */
    protected function valid_answer_message($answer){
        return '';
    }
}
