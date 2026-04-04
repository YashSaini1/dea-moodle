<?php

/**
 * Renderers for outputting parts of the question engine.
 *
 * @package    moodlecore
 * @subpackage questionengine
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_sql\output\core;

use html_writer;
use qbehaviour_renderer;
use qtype_renderer;
use question_attempt;
use question_display_options;

defined('MOODLE_INTERNAL') || die();


/**
 * This renderer controls the overall output of questions. It works with a
 * {@link qbehaviour_renderer} and a {@link qtype_renderer} to output the
 * type-specific bits. The main entry point is the {@link question()} method.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_renderer extends \core_question_renderer {

    /**
     * Generate the display of a question in a particular state, and with certain
     * display options. Normally you do not call this method directly. Intsead
     * you call {@link question_usage_by_activity::render_question()} which will
     * call this method with appropriate arguments.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param string|null $number The question number to display. 'i' is a special
     *      value that gets displayed as Information. Null means no number is displayed.
     * @return string HTML representation of the question.
     */
    public function question(question_attempt $qa, qbehaviour_renderer $behaviouroutput,
            qtype_renderer $qtoutput, question_display_options $options, $number){
        $qtype = $qa->get_question(false)->get_type_name();

        if (!theme_sql_is_custom_question_type($qtype)){
            return parent::question($qa, $behaviouroutput, $qtoutput, $options, $number);
        }

        $question = $qa->get_question();
        $output = '';
        $output .= html_writer::start_tag('div', array(
            'id'    => $qa->get_outer_question_div_unique_id(),
            'class' => implode(' ', array(
                'que',
                'runner_page',
                $qa->get_question(false)->get_type_name(),
                $qa->get_behaviour_name(),
                $qa->get_state_class($options->correctness && $qa->has_marks()),
            )),
        ));

        if ($qtype == 'data_modeling'){
            return $output . $this->render_data_modeling_question($qa, $behaviouroutput, $qtoutput, $options, $number)
                .html_writer::end_tag('div');
        }

        $has_output = $qa->get_last_qt_var('_runneroutput') || $qa->get_last_qt_var('_runnererror');

        $output .= html_writer::start_tag('div', array('class' => 'content'));
        $output .= html_writer::start_div('formulation-wrapper');

        $output .= html_writer::start_div('question-area');
        $output .= $this->render_from_template('theme_sql/editor/tab_buttons', ['has_output' => $has_output, 'video_exist' => (bool)$question->videourl]);
        $output .= html_writer::start_div('tabs_info_container');

        $output .= html_writer::start_div('question-text-container tab tab-question '.(!$has_output ? 'active' : ''));
        $output .= html_writer::tag('div',
            $this->question_text_info($qa, $behaviouroutput, $qtoutput, $options),
            array('class' => 'question-text clearfix'));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_div('editor-output-container tab tab-output '.($has_output ? 'active' : ''));
        $output .= html_writer::start_div('table_answer_wrapper');
        $output .= html_writer::tag('div',
            $this->add_part_heading(get_string('feedback', 'question'),
                $this->outcome($qa, $behaviouroutput, $qtoutput, $options)),
            array('class' => 'outcome clearfix'));
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        if(isset($question->videourl) && !empty($question->videourl)) {
            $output .= html_writer::start_div('video-output-container tab tab-video');
            $output .= html_writer::tag('div',
                $this->video_info($qa, $qtoutput, $question),
                array('class' => 'video-output clearfix'));
            $output .= html_writer::end_div();
        }
        $output .= html_writer::end_div();

        global $attemptid, $cmid;
        $context['url_solution'] = new \moodle_url('/question/type/'.$qtype.'/answer.php',
            ['cmid' => $cmid, 'attemptid' => $attemptid, 'slot' => $qa->get_slot()]);
        $output .= $this->render_from_template('theme_sql/editor/show_solution_button', $context);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('editor_slider');
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('formulation-area');
        $output .= html_writer::tag('h2', get_string('editor', 'h5p'));
        $output .= html_writer::start_div('formulation-container editor_container', ['style' => 'visibility:hidden']);
        $output .= html_writer::tag('div',
            $this->add_part_heading(
                $qtoutput->formulation_heading(),
                $this->formulation($qa, $behaviouroutput, $qtoutput, $options)),
            array('class' => 'formulation clearfix'.(!empty($sqloutput) ? ' show_table' : '')));
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('div');

        /* $output .= html_writer::nonempty_tag('div',
                 $this->add_part_heading(get_string('feedback', 'question'),
                     $this->outcome($qa, $behaviouroutput, $qtoutput, $options)),
                 array('class' => 'outcome clearfix'));*/

        $output .= html_writer::nonempty_tag('div',
            $this->add_part_heading(get_string('comments', 'question'),
                $this->manual_comment($qa, $behaviouroutput, $qtoutput, $options)),
            array('class' => 'comment clearfix'));

        $output .= html_writer::nonempty_tag('div',
            $this->response_history($qa, $behaviouroutput, $qtoutput, $options),
            array('class' => 'history clearfix border p-2'));

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $this->page->requires->js_call_amd('theme_sql/question_tabs', 'init');
        $this->page->requires->js_call_amd('theme_sql/editor_slider', 'init');
        return $output;
    }

    protected function render_data_modeling_question(question_attempt $qa, qbehaviour_renderer $behaviouroutput,
        qtype_renderer $qtoutput, question_display_options $options, $number){
        $qtype = $qa->get_question(false)->get_type_name();
        $question = $qa->get_question();
        $question->qtype->get_data_modeling_options($question);

        $has_output = !empty($qa->get_last_qt_var('answer'));

        $output = html_writer::start_tag('div', array('class' => 'content'));
        $output .= html_writer::start_div('formulation-wrapper editor_opened');
        $output .= html_writer::div(
            html_writer::div('', 'open_editor_button'),
            'open_editor_button_container'
        );

        $output .= html_writer::start_div('question-area');
        $output .= $this->render_from_template('theme_sql/editor/data_modeling_tab_buttons', [
            'has_output' => $has_output,
            'video_exist' => (bool)$question->videourl,
        ]);
        $output .= html_writer::start_div('tabs_info_container');

        $output .= html_writer::start_div('question-text-container tab tab-question '.(!$has_output ? 'active' : ''));
        $output .= html_writer::tag('div',
            $this->question_text_info($qa, $behaviouroutput, $qtoutput, $options),
            array('class' => 'question-text clearfix'));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_div('editor-output-container tab tab-output editor_container '.($has_output ? 'active' : ''),
            ['style' => 'visibility:hidden']);
        $output .= $qtoutput->render_editor_info($qa, $options);
        $output .= html_writer::end_div();

        if(isset($question->videourl) && !empty($question->videourl)) {
            $output .= html_writer::start_div('video-output-container tab tab-video');
            $output .= html_writer::tag('div',
                $this->video_info($qa, $qtoutput, $question),
                array('class' => 'video-output clearfix'));
            $output .= html_writer::end_div();
        }

        $output .= html_writer::end_div();

        if (!empty($qa->get_question()->generalfeedback)){
            global $attemptid, $cmid;
            $context['url_solution'] = new \moodle_url('/question/type/'.$qtype.'/answer.php',
                ['cmid' => $cmid, 'attemptid' => $attemptid, 'slot' => $qa->get_slot()]);
            $output .= $this->render_from_template('theme_sql/editor/show_solution_button', $context);
        }
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('editor_slider');
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('formulation-area');
        $output .= html_writer::tag('h2', get_string('output', 'theme_sql'));
        $output .= html_writer::start_div('formulation-container');
        $output .= html_writer::tag('div',
            $this->add_part_heading(
                $qtoutput->formulation_heading(),
                $this->formulation($qa, $behaviouroutput, $qtoutput, $options)),
            array('class' => 'formulation clearfix'.(!empty($sqloutput) ? ' show_table' : '')));
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

//        $output .= html_writer::start_div('react-area');
//        $output .= html_writer::tag('h2', get_string('output', 'theme_sql'));
//        $output .= 'react contents';
//        $output .= html_writer::end_div();
//        $output .= html_writer::end_tag('div');


        $output .= html_writer::end_tag('div');

        $this->page->requires->js_call_amd('theme_sql/question_tabs', 'init');
        $this->page->requires->js_call_amd('theme_sql/data_modeling_hide_editor', 'init');
        $this->page->requires->js_call_amd('theme_sql/editor_slider', 'init');
        return $output;
    }

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the quetsion text, and the controls for students to
     * input their answers. Some question types also embed feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    protected function formulation(question_attempt $qa, qbehaviour_renderer $behaviouroutput,
        qtype_renderer $qtoutput, question_display_options $options) {
        $qtype = $qa->get_question(false)->get_type_name();
        if (!theme_sql_is_custom_question_type($qtype)){
            return parent::formulation($qa, $behaviouroutput, $qtoutput, $options);
        }

        $output = '';
        $output .= html_writer::empty_tag('input', array(
            'type'  => 'hidden',
            'name'  => $qa->get_control_field_name('sequencecheck'),
            'value' => $qa->get_sequence_check_count(),
        ));

        $output .= $qtoutput->formulation_and_controls($qa, $options);
        if ($options->clearwrong){
            $output .= $qtoutput->clear_wrong($qa);
        }
        $output .= html_writer::nonempty_tag('div', $behaviouroutput->controls($qa, $options), array('class' => 'im-controls'));
        return $output;
    }

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the quetsion text, and the controls for students to
     * input their answers. Some question types also embed feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    protected function question_text_info(question_attempt $qa, qbehaviour_renderer $behaviouroutput,
        qtype_renderer $qtoutput, question_display_options $options) {

        if (method_exists($qtoutput, 'render_question_info')){
            return $qtoutput->render_question_info($qa, $options);
        }
        return '';
    }

    /**
     *
     * @param question_attempt $qa the question attempt to display.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param \question_definition $question the question object
     * @return string HTML fragment.
     */
    protected function video_info(question_attempt $qa, qtype_renderer $qtoutput, \question_definition $question) {

        if (method_exists($qtoutput, 'render_video')){
            return $qtoutput->render_video($qa, $question);
        }
        return '';
    }
}
