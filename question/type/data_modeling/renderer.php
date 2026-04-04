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
 * Data modeling question renderer class.
 *
 * @package    qtype_data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use qtype_sqlrunner\constants;

/**
 * Generates the output for short answer questions.
 *
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class qtype_data_modeling_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $currentanswer = $qa->get_last_qt_var('table_code');
        $inputname = $qa->get_qt_field_name('table_code');
        $inputattributes = array(
            'type' => 'hidden',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => 'data_modeling_table_code',
            'class' => 'form-control d-none',
        );

        $input = html_writer::empty_tag('input', $inputattributes);
        $this->page->requires->js_call_amd('qtype_data_modeling/app-lazy');
        return '<div id="data_modelling_editor" page="editor"></div>' . $input;

        return $result;
    }

    public function render_editor_info(question_attempt $qa, question_display_options $options, $expected = null) {
        $question = $qa->get_question();
        $context = [];
        $responsefieldname = $qa->get_qt_field_name('answer');
        $responsefieldid = 'id_' . $responsefieldname;

        $currentlanguage = 'dbml';

        if (empty($expected)){
            $currentanswer = $qa->get_last_qt_var('answer', false);
            if ($currentanswer === false){
                $currentanswer = dm_str('default_code');
            } else {
                $currentanswer = trim($currentanswer);
            }
        } else {
            $currentanswer = $expected;
        }

        $rows = $question->answerboxlines ?? constants::DEFAULT_NUM_ROWS;
        $taattributes = $this->answerbox_attributes($responsefieldname, $rows, $currentlanguage, $options->readonly);

        $context['currentanswer'] =  $currentanswer;
        $context['taattributes'] = html_writer::attributes($taattributes);

        // Initialise any JavaScript UI. Default is Ace unless uiplugin is explicitly
        // set and is neither the empty string nor the value 'none'.
        // Thanks to Ulrich Dangel for the original implementation of the Ace code editor.
        qtype_sqlrunner_util::load_uiplugin_js($question, $responsefieldid);
        if (!$options->readonly){
            $this->output->page->requires->js_call_amd('qtype_data_modeling/dm_editor_preloader', 'init');
        }
        return $this->output->render_from_template('qtype_sqlrunner/editor_layout', $context);
    }

    protected function answerbox_attributes($fieldname, $rows, $currentlanguage, $readonly=false) {
        $attributes = array(
            'class' => 'sqlrunner-answer edit_code',
            'name' => $fieldname,
            'id' => 'id_' . $fieldname,
            'spellcheck' => 'false',
            'rows' => $rows,
            'data-params' => '{}',
            'data-lang' => ucwords($currentlanguage),
        );

        if ($readonly) {
            $attributes['readonly'] = '';
        }

        return $attributes;
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
        $context = [];
        $context['questiontext'] = $question->format_questiontext($qa);
        return $this->output->render_from_template('qtype_sqlrunner/question_info_layout', $context);
    }

    public function specific_feedback(question_attempt $qa) {
        return '';
        $question = $qa->get_question();

        if ($question->has_separate_unit_field()) {
            $selectedunit = $qa->get_last_qt_var('unit');
        } else {
            $selectedunit = null;
        }
        list($value, $unit, $multiplier) = $question->ap->apply_units(
                $qa->get_last_qt_var('answer'), $selectedunit);
        $answer = $question->get_matching_answer($value, $multiplier);

        if ($answer && $answer->feedback) {
            $feedback = $question->format_text($answer->feedback, $answer->feedbackformat,
                    $qa, 'question', 'answerfeedback', $answer->id);
        } else {
            $feedback = '';
        }

        if ($question->unitgradingtype && !$question->ap->is_known_unit($unit)) {
            $feedback .= html_writer::tag('p', get_string('unitincorrect', 'qtype_data_modeling'));
        }

        return $feedback;
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $answer = $question->get_correct_answer();
        if (empty($answer)) {
            return '';
        }

        return get_string('correctansweris', 'qtype_shortanswer', $answer);
    }

    public function feedback(question_attempt $qa, question_display_options $options){
        $output = '';
        $hint = null;

        if ($options->feedback) {
            $output .= html_writer::nonempty_tag('div', $this->specific_feedback($qa),
                array('class' => 'specificfeedback'));
            $hint = $qa->get_applicable_hint();
        }

        if ($options->numpartscorrect) {
            $output .= html_writer::nonempty_tag('div', $this->num_parts_correct($qa),
                array('class' => 'numpartscorrect'));
        }

        if ($hint) {
            $output .= $this->hint($qa, $hint);
        }
        // remove general feedback

        if ($options->rightanswer) {
            $output .= html_writer::nonempty_tag('div', $this->correct_response($qa),
                array('class' => 'rightanswer'));
        }

        return $output;
    }

    public function render_video(question_attempt $qa, question_definition $question) {
        $question->qtype->get_data_modeling_options($question);
        $context = [];
        $context['videourl'] = $question->videourl ?? null;
        return $this->output->render_from_template('qtype_sqlrunner/video_layout', $context);
    }
}
