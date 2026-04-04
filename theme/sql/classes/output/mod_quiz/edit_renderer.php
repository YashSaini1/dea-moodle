<?php

/**
 * Renderer outputting the quiz editing UI.
 *
 * @package mod_quiz
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_sql\output\mod_quiz;
defined('MOODLE_INTERNAL') || die();

use mod_quiz\question\bank\qbank_helper;
use \mod_quiz\structure;
use \html_writer;
use renderable;

/**
 * Renderer outputting the quiz editing UI.
 *
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */
class edit_renderer extends \mod_quiz\output\edit_renderer {
    /**
     * Render the edit page
     *
     * @param \quiz $quizobj object containing all the quiz settings information.
     * @param structure $structure object containing the structure of the quiz.
     * @param \core_question\local\bank\question_edit_contexts $contexts the relevant question bank contexts.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @return string HTML to output.
     */
    public function edit_page(\quiz $quizobj, structure $structure,
        \core_question\local\bank\question_edit_contexts $contexts, \moodle_url $pageurl, array $pagevars) {
        $output = '';

        // Page title.
        $output .= $this->heading(get_string('questions', 'quiz'));

        // Information at the top.
//        $output .= $this->quiz_state_warnings($structure);

        $output .= html_writer::start_div('mod_quiz-edit-top-controls');

        $output .= html_writer::start_div('d-flex justify-content-between flex-wrap mb-1');
        $output .= html_writer::start_div('d-flex flex-column justify-content-around');
        $output .= $this->quiz_information($structure);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_div('d-flex justify-content-between flex-wrap mb-1');
        // hide this because quiz Yui js needs this elements
        $output .= html_writer::start_div('mod_quiz-edit-action-buttons btn-group edit-toolbar',
            ['role' => 'group', 'style' => 'display:none']);
        $output .= $this->repaginate_button($structure, $pageurl);
        $output .= $this->selectmultiple_button($structure);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');

        $output .= $this->selectmultiple_controls($structure);
        $output .= html_writer::end_tag('div');

        // Show the questions organised into sections and pages.
        $output .= $this->start_section_list($structure);

        foreach ($structure->get_sections() as $section) {
            $output .= $this->start_section($structure, $section);
            $output .= $this->questions_in_section($structure, $section, $contexts, $pagevars, $pageurl);

            if ($structure->is_last_section($section)) {
                $output .= \html_writer::start_div('last-add-menu');
                $output .= html_writer::tag('span', $this->add_menu_actions($structure, 0,
                        $pageurl, $contexts, $pagevars), array('class' => 'add-menu-outer'));
                $output .= \html_writer::end_div();
            }

            $output .= $this->end_section();
        }

        $output .= $this->end_section_list();

        // Initialise the JavaScript.
        $this->initialise_editing_javascript($structure, $contexts, $pagevars, $pageurl);

        // Include the contents of any other popups required.
        if ($structure->can_be_edited()) {
            $thiscontext = $contexts->lowest();
            $this->page->requires->js_call_amd('mod_quiz/quizquestionbank', 'init', [
                $thiscontext->id
            ]);

            $this->page->requires->js_call_amd('mod_quiz/add_random_question', 'init', [
                $thiscontext->id,
                $pagevars['cat'],
                $pageurl->out_as_local_url(true),
                $pageurl->param('cmid'),
                \core\plugininfo\qbank::is_plugin_enabled(\qbank_managecategories\helper::PLUGINNAME),
            ]);

            // Include the question chooser.
            $output .= $this->question_chooser();
        }

        return $output;
    }

    /**
     * Render the status bar.
     *
     * @param structure $structure the quiz structure.
     * @return string HTML to output.
     */
    public function quiz_information(structure $structure) {
        list($currentstatus, $explanation) = $structure->get_dates_summary();

        $output = html_writer::span(
            get_string('numquestionsx', 'quiz', $structure->get_question_count()),
            'numberofquestions');

        return html_writer::div($output, 'statusbar');
    }

    /**
     * Display the start of a section, before the questions.
     *
     * @param structure $structure the structure of the quiz being edited.
     * @param \stdClass $section The quiz_section entry from DB
     * @return string HTML to output.
     */
    protected function start_section($structure, $section) {

        $output = '';

        $sectionstyle = '';
        if ($structure->is_only_one_slot_in_section($section)) {
            $sectionstyle = ' only-has-one-slot';
        }

        $output .= html_writer::start_tag('li', array('id' => 'section-'.$section->id,
            'class' => 'section main clearfix'.$sectionstyle, 'role' => 'region',
            'aria-label' => $section->heading));

        $output .= html_writer::start_div('content');

        return $output;
    }

    /**
     * Displays one question with the surrounding controls.
     *
     * @param structure $structure object containing the structure of the quiz.
     * @param int $slot which slot we are outputting.
     * @param \core_question\local\bank\question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link \question_edit_setup()}.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @return string HTML to output.
     */
    public function question_row(structure $structure, $slot, $contexts, $pagevars, $pageurl) {
        $output = '';

        $output .= $this->page_row($structure, $slot, $contexts, $pagevars, $pageurl);

        // Page split/join icon.
        $joinhtml = '';
        // Question HTML.
        $questionhtml = $this->question($structure, $slot, $pageurl);
        $qtype = $structure->get_question_type_for_slot($slot);
        $questionclasses = 'activity ' . $qtype . ' qtype_' . $qtype . ' slot';

        $output .= html_writer::tag('li', $questionhtml . $joinhtml,
                array('class' => $questionclasses, 'id' => 'slot-' . $structure->get_slot_id_for_slot($slot),
                        'data-canfinish' => $structure->can_finish_during_the_attempt($slot)));

        return $output;
    }

    /**
     * Displays one question with the surrounding controls.
     *
     * @param structure $structure object containing the structure of the quiz.
     * @param int $slot the first slot on the page we are outputting.
     * @param \core_question\local\bank\question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link \question_edit_setup()}.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @return string HTML to output.
     */
    public function page_row(structure $structure, $slot, $contexts, $pagevars, $pageurl) {
        $output = '';

        $pagenumber = $structure->get_page_number_for_slot($slot);

        // Put page in a heading for accessibility and styling.
        $page = '';
        if ($structure->is_first_slot_on_page($slot)) {
            // Add the add-menu at the page level.
            $addmenu = html_writer::tag('span', $this->add_menu_actions($structure,
                $pagenumber, $pageurl, $contexts, $pagevars), array('class' => 'add-menu-outer'));

            $addquestionform = $this->add_question_form($structure, $pagenumber, $pageurl, $pagevars);

            // Not delete pages because this elements needs in mod_quiz Yui js
            $output .= html_writer::tag('li', $page . $addmenu . $addquestionform,
                array('class' => 'pagenumber activity yui3-dd-drop page', 'id' => 'page-' . $pagenumber,
                      'style' => 'display:none'));
        }

        return $output;
    }

    /**
     * Returns the list of actions to go in the add menu.
     * @param structure $structure object containing the structure of the quiz.
     * @param int $page the page number that this menu will add to.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @param array $pagevars the variables from {@link \question_edit_setup()}.
     * @return array the actions.
     */
    public function edit_menu_actions(structure $structure, $page, \moodle_url $pageurl, array $pagevars) {
        $questioncategoryid = question_get_category_id_from_pagevars($pagevars);
        static $str;
        if (!isset($str)) {
            $str = get_strings(array('addasection', 'addaquestion', 'addarandomquestion',
                    'addarandomselectedquestion', 'questionbank'), 'quiz');
        }

        // Get section, page, slotnumber and maxmark.
        $actions = array();

        // Add a new question to the quiz.
        $returnurl = new \moodle_url($pageurl, array('addonpage' => $page));
        $params = array('returnurl' => $returnurl->out_as_local_url(false),
                'cmid' => $structure->get_cmid(), 'category' => $questioncategoryid,
                'addonpage' => $page, 'appendqnumstring' => 'addquestion');

        $actions['addaquestion'] = new \action_menu_link_secondary(
            new \moodle_url('/question/bank/editquestion/addquestion.php', $params),
            new \pix_icon('t/add', $str->addaquestion, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->addaquestion, array('class' => 'cm-edit-action addquestion', 'data-action' => 'addquestion')
        );

        // Do not call question bank.

        // Add a new section to the add_menu if possible. This is always added to the HTML
        // then hidden with CSS when no needed, so that as things are re-ordered, etc. with
        // Ajax it can be relevaled again when necessary.
        $params = array('cmid' => $structure->get_cmid(), 'addsectionatpage' => $page);

        $actions['addasection'] = new \action_menu_link_secondary(
            new \moodle_url($pageurl, $params),
            new \pix_icon('t/add', $str->addasection, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->addasection, array('class' => 'cm-edit-action addasection', 'data-action' => 'addasection')
        );

        return $actions;
    }

    /**
     * Display a question.
     *
     * @param structure $structure object containing the structure of the quiz.
     * @param int $slot the first slot on the page we are outputting.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @return string HTML to output.
     */
    public function question(structure $structure, int $slot, \moodle_url $pageurl) {
        // Get the data required by the question_slot template.
        $slotid = $structure->get_slot_id_for_slot($slot);

        $output = '';
        $output .= html_writer::start_tag('div');

        if ($structure->can_be_edited()) {
            $output .= $this->question_move_icon($structure, $slot);
        }

        $data = [
            'slotid' => $slotid,
            'canbeedited' => $structure->can_be_edited(),
            'checkbox' => $this->get_checkbox_render($structure, $slot),
            'questionnumber' => $this->question_number($structure->get_displayed_number_for_slot($slot)),
            'questionname' => $this->get_question_name_for_slot($structure, $slot, $pageurl),
            'questionicons' => $this->get_action_icon($structure, $slot, $pageurl),
            'questiondependencyicon' => ($structure->can_be_edited() ? $this->question_dependency_icon($structure, $slot) : ''),
            'versionselection' => false
        ];

        $data['versionoptions'] = [];

        // Render the question slot template.
        $output .= $this->render_from_template('mod_quiz/question_slot', $data);

        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Get the action icons render.
     *
     * @param structure $structure object containing the structure of the quiz.
     * @param int $slot the slot on the page we are outputting.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @return string HTML to output.
     */
    public function get_action_icon(structure $structure, int $slot, \moodle_url $pageurl) : string {
        // Action icons.
        $questionicons = '';

        // save only delete icon
        $questionicons .= $this->question_remove_icon($structure, $slot, $pageurl);

        return $questionicons;
    }

    /**
     * Initialise the JavaScript for the general editing. (JavaScript for popups
     * is handled with the specific code for those.)
     *
     * @param structure $structure object containing the structure of the quiz.
     * @param \core_question\local\bank\question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link \question_edit_setup()}.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @return bool Always returns true
     */
    protected function initialise_editing_javascript(structure $structure,
            \core_question\local\bank\question_edit_contexts $contexts, array $pagevars, \moodle_url $pageurl) {
        parent::initialise_editing_javascript($structure, $contexts, $pagevars, $pageurl);
        $this->page->requires->js_call_amd('theme_sql/quiz_drag_drop', 'init');
        return true;
    }
}
