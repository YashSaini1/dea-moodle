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
 * Defines the quiz module ettings form.
 *
 * @package    mod_quiz
 * @copyright  2006 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/mod_form.php');

/**
 * Settings form for the quiz module.
 *
 * @copyright  2006 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sql_mod_quiz_mod_form extends \mod_quiz_mod_form {

    public function __construct($current, $section, $cm, $course) {
        $this->_modname = 'quiz';
        parent::__construct($current, $section, $cm, $course);
    }

    protected function definition() {
        if (is_siteadmin()){
            parent::definition();
            return;
        }

        global $COURSE, $CFG, $DB, $PAGE;
        $quizconfig = get_config('quiz');
        $mform = $this->_form;

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Introduction.
        $this->standard_intro_elements(get_string('introduction', 'quiz'));

        // -------------------------------------------------------------------------------
        // Open and close dates.
        $mform->addElement('hidden', 'timeopen', 0);
        $mform->setType('timeopen', PARAM_INT);

        $mform->addElement('hidden', 'timeclose', 0);
        $mform->setType('timeclose', PARAM_INT);

        $mform->addElement('hidden', 'timelimit', 0);
        $mform->setType('timelimit', PARAM_INT);

        // What to do with overdue attempts.
        // see quiz_get_overdue_handling_options()
        $mform->addElement('hidden', 'overduehandling', 'autosubmit');
        $mform->setType('overduehandling', PARAM_TEXT);

        // Grace period time.
        $mform->addElement('hidden', 'graceperiod', 0);
        $mform->setType('graceperiod', PARAM_INT);

        // -------------------------------------------------------------------------------

        // close <div id="headername"> and fieldset. Add custom wrapper. After this, create closed tages, because moodle will close them again
        $mform->addElement('html', '</div></fieldset> <div style="display:none"><fieldset><div>');

        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        $mform->removeElement('grade');
        if (property_exists($this->current, 'grade')) {
            $currentgrade = $this->current->grade;
        } else {
            $currentgrade = $quizconfig->maximumgrade;
        }
        $mform->addElement('hidden', 'grade', $currentgrade);
        $mform->setType('grade', PARAM_FLOAT);

        // Number of attempts.
        $mform->addElement('hidden', 'attempts', 0); // 0 equals unlimited
        $mform->setType('attempts', PARAM_INT);

        // Grading method.
        $mform->addElement('hidden', 'grademethod', QUIZ_ATTEMPTLAST);
        $mform->setType('grademethod', PARAM_INT);

        // -------------------------------------------------------------------------------
        $mform->addElement('hidden', 'questionsperpage', $quizconfig->questionsperpage);
        $mform->setType('questionsperpage', PARAM_INT);

        // Navigation method.
        $mform->addElement('hidden', 'navmethod', QUIZ_NAVMETHOD_FREE);
        $mform->setType('navmethod', PARAM_TEXT);

        // -------------------------------------------------------------------------------

        // Shuffle within questions.
        $mform->addElement('hidden', 'shuffleanswers', 0);
        $mform->setType('shuffleanswers', PARAM_INT);

        // How questions behave (question behaviour).
        if (!empty($this->current->preferredbehaviour)) {
            $currentbehaviour = $this->current->preferredbehaviour;
        } else {
            $currentbehaviour = '';
        }
        $behaviours = question_engine::get_behaviour_options($currentbehaviour);
        $mform->addElement('select', 'preferredbehaviour', get_string('howquestionsbehave', 'question'), $behaviours);
        $mform->addHelpButton('preferredbehaviour', 'howquestionsbehave', 'question');

        // Can redo completed questions.
        $mform->addElement('hidden', 'canredoquestions', 0); // 0 eq No
        $mform->setType('canredoquestions', PARAM_INT);

        // Each attempt builds on last.
        $mform->addElement('hidden', 'attemptonlast', 0); // 0 eq No
        $mform->setType('attemptonlast', PARAM_INT);

        // -------------------------------------------------------------------------------

        // Review options.
//        $this->add_review_options_group($mform, $quizconfig, 'during',
//                mod_quiz_display_options::DURING, true);
//        $this->add_review_options_group($mform, $quizconfig, 'immediately',
//                mod_quiz_display_options::IMMEDIATELY_AFTER);
//        $this->add_review_options_group($mform, $quizconfig, 'open',
//                mod_quiz_display_options::LATER_WHILE_OPEN);
//        $this->add_review_options_group($mform, $quizconfig, 'closed',
//                mod_quiz_display_options::AFTER_CLOSE);

        // -------------------------------------------------------------------------------
        // Show user picture.
        $mform->addElement('hidden', 'showuserpicture', 0); // 0 eq No
        $mform->setType('showuserpicture', PARAM_INT);

        $mform->addElement('hidden', 'decimalpoints', 2); // Set 2 dedmicals in grade
        $mform->setType('decimalpoints', PARAM_INT);

        // Question decimal points.
        $mform->addElement('hidden', 'questiondecimalpoints', -1); // -1 eq sameasoverall
        $mform->setType('questiondecimalpoints', PARAM_INT);

        // Show blocks during quiz attempt.
        $mform->addElement('hidden', 'showblocks', 1); // Set to 1, custom setting
        $mform->setType('showblocks', PARAM_INT);

        // -------------------------------------------------------------------------------
        // Require password to begin quiz attempt.
        $mform->addElement('hidden', 'quizpassword', null);
        $mform->setType('quizpassword', PARAM_TEXT);

        // Enforced time delay between quiz attempts.

        // Browser security choices.

        // Any other rule plugins.

        // -------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

        // Check and act on whether setting outcomes is considered an advanced setting.

        // The standard_coursemodule_elements method sets this to 100, but the
        // quiz has its own setting, so use that.
        $mform->setDefaults(['grade' => $quizconfig->maximumgrade, 'gradepass' => $quizconfig->maximumgrade]);

        // -------------------------------------------------------------------------------
        $this->apply_admin_defaults();

        // close the same tags, that in open.
        $mform->addElement('html', '</div></fieldset></div><fieldset>');


        $this->add_action_buttons();

        $PAGE->requires->yui_module('moodle-mod_quiz-modform', 'M.mod_quiz.modform.init');
    }

    /**
     * Overriding formslib's add_action_buttons() method, to add an extra submit "save changes and return" button.
     *
     * @param bool $cancel show cancel button
     * @param string $submitlabel null means default, false means none, string is label text
     * @param string $submit2label  null means default, false means none, string is label text
     * @return void
     */
    function add_action_buttons($cancel=true, $submitlabel=null, $submit2label=null) {
        if (is_siteadmin()){
            parent::add_action_buttons($cancel, $submitlabel, $submit2label);
            return;
        }

        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechangesanddisplay');
        }

        if (is_null($submit2label)) {
            $submit2label = get_string('savechangesandreturntocourse');
        }

        $mform = $this->_form;

        $mform->addElement('static', 'header_closer');
        $mform->closeHeaderBefore('header_closer');

        $mform->closeHeaderBefore('coursecontentnotification');
        // elements in a row need a group
        $buttonarray = array();

        // Label for the submit button to return to the course.
        // Ignore this button in single activity format because it is confusing.
        if ($submit2label !== false && $this->courseformat->has_view_page()) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', $submit2label);
        }

        if ($submitlabel !== false) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        }

        if ($cancel) {
            $buttonarray[] = &$mform->createElement('cancel');
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
    }

    public function data_preprocessing(&$toform) {
        if (isset($toform['grade'])) {
            // Convert to a real number, so we don't get 0.0000.
            $toform['grade'] = $toform['grade'] + 0;
            $toform['gradepass'] = $toform['grade'];
        }

        $toform['attemptduring'] = true;
        $toform['overallfeedbackduring'] = false;

        // Password field - different in form to stop browsers that remember
        // passwords from getting confused.

        // Load any settings belonging to the access rules.
        if (empty($toform['completionminattempts'])) {
            $toform['completionminattempts'] = 1;
        } else {
            $toform['completionminattemptsenabled'] = $toform['completionminattempts'] > 0;
        }
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionminattemptsenabled) || !$autocompletion) {
                $data->completionminattempts = 0;
            }
        }
        $data->gradepass = $data->grade;
    }

    public function validation($data, $files) {
        $errors = moodleform_mod::validation($data, $files);

        // Check that the grace period is not too short.
        if ($data['overduehandling'] == 'graceperiod') {
            $graceperiodmin = get_config('quiz', 'graceperiodmin');
            if ($data['graceperiod'] <= $graceperiodmin) {
                $errors['graceperiod'] = get_string('graceperiodtoosmall', 'quiz', format_time($graceperiodmin));
            }
        }

        if (!empty($data['completionminattempts'])) {
            if ($data['attempts'] > 0 && $data['completionminattempts'] > $data['attempts']) {
                $errors['completionminattemptsgroup'] = get_string('completionminattemptserror', 'quiz');
            }
        }

        // If CBM is involved, don't show the warning for grade to pass being larger than the maximum grade.
        // Any other rule plugins.

        return $errors;
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $items = array();

        $mform->addElement('advcheckbox', 'completionattemptsexhausted', null,
            get_string('completionattemptsexhausted', 'quiz'),
            array('group' => 'cattempts'));
        $mform->disabledIf('completionattemptsexhausted', 'completionpassgrade', 'notchecked');
        $items[] = 'completionattemptsexhausted';

        $group = array();
        $group[] = $mform->createElement('checkbox', 'completionminattemptsenabled', '',
            get_string('completionminattempts', 'quiz'));
        $group[] = $mform->createElement('text', 'completionminattempts', '', array('size' => 3));
        $mform->setType('completionminattempts', PARAM_INT);
        $mform->addGroup($group, 'completionminattemptsgroup', get_string('completionminattemptsgroup', 'quiz'), array(' '), false);
        $mform->disabledIf('completionminattempts', 'completionminattemptsenabled', 'notchecked');

        $items[] = 'completionminattemptsgroup';

        return $items;
    }
}
