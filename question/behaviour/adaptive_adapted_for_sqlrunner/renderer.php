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
 * @package    qbehaviour_adaptive_adapted_for_sqlrunner
 * @copyright  2016 Richard Lobb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/behaviour/adaptive/renderer.php');

class qbehaviour_adaptive_adapted_for_sqlrunner_renderer extends qbehaviour_adaptive_renderer
{
    public function controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $buttons = '';
        if (!empty($question->precheck)) {
            $buttons .= $this->precheck_button($qa, $options);
        }
        if (empty($question->hidecheck)) {
            // We want check to be a primary button, to contrast with the others.
            if ($question->get_type_name() != 'data_modeling'){
                $buttons .= str_replace('btn-secondary', 'btn-primary', $this->submit_button($qa, $options));
            }
            /** Add 'next' button
             * @see mod_quiz_renderer::attempt_navigation_buttons() */
            $nextlabel = get_string('submit_answer', 'theme_sql');
            $buttons .= html_writer::start_div('submit_answer');
            $buttons .= html_writer::empty_tag('input', array(
                    'type'  => 'submit',
                    'name'  => $qa->get_behaviour_field_name('next'),
                    'value' => $nextlabel,
                    'class' => 'mod_quiz-next-nav btn btn-primary',
                    'id'    =>  'mod_quiz-next-nav',
                )
            );
            $buttons .= html_writer::end_tag('div');
            $this->page->requires->js_call_amd('core_form/submit', 'init', ['mod_quiz-next-nav']);
        }
        if ($qa->get_behaviour()->is_give_up_avaiable_now()) {
            // Put in the space with a flex-grow div, not margins,
            // because that works with both LTR and RTL languages.
            $buttons .= html_writer::div('', 'flex-grow-1');
            $buttons .= $this->give_up_button($qa, $options);
        }
        return html_writer::div($buttons, 'd-flex submit-buttons');
    }

    /**
     * Several behaviours need a submit button, so put the common code here.
     * The button is disabled if the question is displayed read-only.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    protected function submit_button(question_attempt $qa, question_display_options $options) {
        if (!$qa->get_state()->is_active()) {
            return '';
        }
        $attributes = array(
            'type' => 'submit',
            'id' => $qa->get_behaviour_field_name('submit'),
            'name' => $qa->get_behaviour_field_name('submit'),
            'value' => get_string('run', 'qbehaviour_adaptive_adapted_for_sqlrunner'),
            'class' => 'submit btn btn-secondary',
        );
        if ($options->readonly) {
            $attributes['disabled'] = 'disabled';
        }
        $output = html_writer::empty_tag('input', $attributes);
        if (!$options->readonly) {
            $this->page->requires->js_init_call('M.core_question_engine.init_submit_button',
                array($attributes['id']));
        }
        return $output;
    }

    /**
     * Construct the HTML for the optional 'precheck' button, which triggers
     * a partial submit in which no penalties are imposed but only the
     * 'Use as example' test cases are run.
     * This code is identical to the 'submit_button' code in
     * qbehaviour_renderer::submit_button except for the id and name of the
     * button.
     */
    protected function precheck_button(question_attempt $qa, question_display_options $options) {
        if (!$qa->get_state()->is_active()) {
            return '';  // This happens when we are on the Quiz review page, after the attempt is submitted.
        }
        $attributes = array(
            'type' => 'submit',
            'id' => $qa->get_behaviour_field_name('precheck'),
            'name' => $qa->get_behaviour_field_name('precheck'),
            'value' => get_string('precheck', 'qbehaviour_adaptive_adapted_for_sqlrunner'),
            'class' => 'submit btn btn-secondary',
        );
        if ($options->readonly) {
            $attributes['disabled'] = 'disabled';
        }
        $output = html_writer::empty_tag('input', $attributes);
        if (!$options->readonly) {
            $this->page->requires->js_init_call('M.core_question_engine.init_submit_button',
                    array($attributes['id'], $qa->get_slot()));
        }
        return $output;
    }

    /**
     * Render the button to stop the current attempt (if required).
     *
     * @param question_attempt $qa the current attempt at this question.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    protected function give_up_button(question_attempt $qa, question_display_options $options): string {
        if (!$qa->get_state()->is_active()) {
            return '';  // This happens when we are on the Quiz review page, after the attempt is submitted.
        }
        $attributes = array(
            'type' => 'submit',
            'id' => $qa->get_behaviour_field_name('finish'),
            'name' => $qa->get_behaviour_field_name('finish'),
            'value' => get_string('giveup', 'qbehaviour_adaptive_adapted_for_sqlrunner'),
            'class' => 'submit btn btn-danger mx-0',
        );
        if ($options->readonly) {
            $attributes['disabled'] = 'disabled';
        }
        $output = html_writer::empty_tag('input', $attributes);
        if (!$options->readonly) {
            $this->page->requires->js_init_call('M.core_question_engine.init_submit_button',
                    array($attributes['id'], $qa->get_slot()));
        }
        return $output;
    }

    // Override superclass method to suppress feedback on prechecks.
    public function feedback(question_attempt $qa, question_display_options $options) {
        if ($qa->get_last_behaviour_var('_precheck', 0)) {
            return '';
        }
        /// parent method here

        // If the latest answer was invalid, display an informative message.
        if ($qa->get_state() == question_state::$invalid) {
            return '';
            return html_writer::nonempty_tag('div', $this->disregarded_info(), array('class' => 'gradingdetails'));
        }

        // Otherwise get the details.
        return $this->render_adaptive_marks($qa->get_behaviour()->get_adaptive_marks(), $options);
    }
}
