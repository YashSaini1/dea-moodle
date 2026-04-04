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
 * Form for creating new H5P Content
 *
 * @package    mod_hvp
 * @copyright  2016 Joubel AS <contact@joubel.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_grades\component_gradeitems;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hvp/mod_form.php');

class sql_mod_hvp_mod_form extends \mod_hvp_mod_form {

    public function __construct($current, $section, $cm, $course) {
        $this->_modname = 'hvp';
        parent::__construct($current, $section, $cm, $course);
    }

    public function definition() {
        global $CFG, $COURSE, $PAGE;

        $mform =& $this->_form;

        // Name.
        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', PARAM_TEXT);
        $this->_features->showdescription = false;

        // Intro.
        if (method_exists($this, 'standard_intro_elements')) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor(false, get_string('intro', 'hvp'));
        }

        // Action.
        $h5paction = array();
        $h5paction[] = $mform->createElement('radio', 'h5paction', '', get_string('upload', 'hvp'), 'upload');
        $h5paction[] = $mform->createElement('radio', 'h5paction', '', get_string('create', 'hvp'), 'create');
        $mform->addGroup($h5paction, 'h5pactiongroup', get_string('action', 'hvp'), array('<br/>'), false);
        $mform->setDefault('h5paction', 'create');

        // Upload.
        $mform->addElement('filepicker', 'h5pfile', get_string('h5pfile', 'hvp'), null,
            array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => '*'));

        // Editor placeholder.
        if ($CFG->theme == 'boost' || in_array('boost', $PAGE->theme->parents)) {
            $h5peditor   = [];
            $h5peditor[] = $mform->createElement('html',
                                                 '<div class="h5p-editor">' . get_string('javascriptloading', 'hvp') . '</div>');
            $mform->addGroup($h5peditor, 'h5peditor', get_string('editor', 'hvp'));
        } else {
            $mform->addElement('static', 'h5peditor', get_string('editor', 'hvp'),
                               '<div class="h5p-editor">' . get_string('javascriptloading', 'hvp') . '</div>');
        }

        // Hidden fields.
        $mform->addElement('hidden', 'h5plibrary', '');
        $mform->setType('h5plibrary', PARAM_RAW);
        $mform->addElement('hidden', 'h5pparams', '');
        $mform->setType('h5pparams', PARAM_RAW);
        $mform->addElement('hidden', 'h5pmaxscore', '');
        $mform->setType('h5pmaxscore', PARAM_INT);

        $core = \mod_hvp\framework::instance();
        $displayoptions = $core->getDisplayOptionsForEdit();
        if (isset($displayoptions[H5PCore::DISPLAY_OPTION_FRAME])) {
            // Display options group.
            $mform->addElement('header', 'displayoptions', get_string('displayoptions', 'hvp'));

            $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_FRAME, get_string('enableframe', 'hvp'));
            $mform->setType(H5PCore::DISPLAY_OPTION_FRAME, PARAM_BOOL);
            $mform->setDefault(H5PCore::DISPLAY_OPTION_FRAME, true);

            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_DOWNLOAD])) {
                $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_DOWNLOAD, get_string('enabledownload', 'hvp'));
                $mform->setType(H5PCore::DISPLAY_OPTION_DOWNLOAD, PARAM_BOOL);
                $mform->setDefault(H5PCore::DISPLAY_OPTION_DOWNLOAD, $displayoptions[H5PCore::DISPLAY_OPTION_DOWNLOAD]);
                $mform->disabledIf(H5PCore::DISPLAY_OPTION_DOWNLOAD, 'frame');
            }

            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_EMBED])) {
                $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_EMBED, get_string('enableembed', 'hvp'));
                $mform->setType(H5PCore::DISPLAY_OPTION_EMBED, PARAM_BOOL);
                $mform->setDefault(H5PCore::DISPLAY_OPTION_EMBED, $displayoptions[H5PCore::DISPLAY_OPTION_EMBED]);
                $mform->disabledIf(H5PCore::DISPLAY_OPTION_EMBED, 'frame');
            }

            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_COPYRIGHT])) {
                $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_COPYRIGHT, get_string('enablecopyright', 'hvp'));
                $mform->setType(H5PCore::DISPLAY_OPTION_COPYRIGHT, PARAM_BOOL);
                $mform->setDefault(H5PCore::DISPLAY_OPTION_COPYRIGHT, $displayoptions[H5PCore::DISPLAY_OPTION_COPYRIGHT]);
                $mform->disabledIf(H5PCore::DISPLAY_OPTION_COPYRIGHT, 'frame');
            }
        }

        if (!is_siteadmin()){
            $mform->addElement('html', '</div></fieldset> <div style="display:none"><fieldset><div>');
        }
        // Grade settings.
        $this->standard_grading_coursemodule_elements();
        $mform->removeElement('grade');

        // Max grade.
        $mform->addElement('text', 'maximumgrade', get_string('maximumgrade', 'hvp'));
        $mform->setType('maximumgrade', PARAM_INT);
        $mform->setDefault('maximumgrade', 10);

        // Standard course module settings.
        $this->standard_coursemodule_elements();
        if (!is_siteadmin()){
            $mform->addElement('html', '</div></fieldset></div><fieldset>');
        }
        $this->add_action_buttons();
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
        if(is_siteadmin()){
            parent::add_action_buttons($cancel, $submitlabel,$submit2label);
            return;
        }
        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechangesanddisplay');
        }

        if (is_null($submit2label)) {
            $submit2label = get_string('savechangesandreturntocourse');
        }

        $mform = $this->_form;

        $mform->addElement('html', '');
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

    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
    }

    /**
     * Adds all the standard elements to a form to edit the settings for an activity module.
     */
    protected function standard_coursemodule_elements() {
        global $COURSE, $CFG, $DB;
        $mform =& $this->_form;

        $this->_outcomesused = false;
        if ($this->_features->outcomes) {
            if ($outcomes = grade_outcome::fetch_all_available($COURSE->id)) {
                $this->_outcomesused = true;
                $mform->addElement('header', 'modoutcomes', get_string('outcomes', 'grades'));
                foreach($outcomes as $outcome) {
                    $mform->addElement('advcheckbox', 'outcome_'.$outcome->id, $outcome->get_name());
                }
            }
        }

        if ($this->_features->rating) {
            $this->add_rating_settings($mform, 0);
        }

        $mform->addElement('header', 'modstandardelshdr', get_string('modstandardels', 'form'));

        $section = get_fast_modinfo($COURSE)->get_section_info($this->_section);
        $allowstealth =
            !empty($CFG->allowstealth) &&
            $this->courseformat->allow_stealth_module_visibility($this->_cm, $section) &&
            !$this->_features->hasnoview;
        if ($allowstealth && $section->visible) {
            $modvisiblelabel = 'modvisiblewithstealth';
        } else if ($section->visible) {
            $modvisiblelabel = 'modvisible';
        } else {
            $modvisiblelabel = 'modvisiblehiddensection';
        }
        $mform->addElement('modvisible', 'visible', get_string($modvisiblelabel), null,
            array('allowstealth' => $allowstealth, 'sectionvisible' => $section->visible, 'cm' => $this->_cm));
        $mform->addHelpButton('visible', $modvisiblelabel);
        if (!empty($this->_cm) && !has_capability('moodle/course:activityvisibility', $this->get_context())) {
            $mform->hardFreeze('visible');
        }

        if ($this->_features->idnumber) {
            $mform->addElement('text', 'cmidnumber', get_string('idnumbermod'));
            $mform->setType('cmidnumber', PARAM_RAW);
            $mform->addHelpButton('cmidnumber', 'idnumbermod');
        }

        if (has_capability('moodle/course:setforcedlanguage', $this->get_context())) {
            $languages = ['' => get_string('forceno')];
            $languages += get_string_manager()->get_list_of_translations();

            $mform->addElement('select', 'lang', get_string('forcelanguage'), $languages);
        }

        if ($CFG->downloadcoursecontentallowed) {
            $choices = [
                DOWNLOAD_COURSE_CONTENT_DISABLED => get_string('no'),
                DOWNLOAD_COURSE_CONTENT_ENABLED => get_string('yes'),
            ];
            $mform->addElement('select', 'downloadcontent', get_string('downloadcontent', 'course'), $choices);
            $downloadcontentdefault = $this->_cm->downloadcontent ?? DOWNLOAD_COURSE_CONTENT_ENABLED;
            $mform->addHelpButton('downloadcontent', 'downloadcontent', 'course');
            if (has_capability('moodle/course:configuredownloadcontent', $this->get_context())) {
                $mform->setDefault('downloadcontent', $downloadcontentdefault);
            } else {
                $mform->hardFreeze('downloadcontent');
                $mform->setConstant('downloadcontent', $downloadcontentdefault);
            }
        }

        if ($this->_features->groups) {
            $options = array(NOGROUPS       => get_string('groupsnone'),
                             SEPARATEGROUPS => get_string('groupsseparate'),
                             VISIBLEGROUPS  => get_string('groupsvisible'));
            $mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $options, NOGROUPS);
            $mform->addHelpButton('groupmode', 'groupmode', 'group');
        }

        if ($this->_features->groupings) {
            // Groupings selector - used to select grouping for groups in activity.
            $options = array();
            if ($groupings = $DB->get_records('groupings', array('courseid'=>$COURSE->id))) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            core_collator::asort($options);
            $options = array(0 => get_string('none')) + $options;
            $mform->addElement('select', 'groupingid', get_string('grouping', 'group'), $options);
            $mform->addHelpButton('groupingid', 'grouping', 'group');
        }

        if (!empty($CFG->enableavailability)) {
            // Add special button to end of previous section if groups/groupings
            // are enabled.

            $availabilityplugins = \core\plugininfo\availability::get_enabled_plugins();
            $groupavailability = $this->_features->groups && array_key_exists('group', $availabilityplugins);
            $groupingavailability = $this->_features->groupings && array_key_exists('grouping', $availabilityplugins);

            if ($groupavailability || $groupingavailability) {
                // When creating the button, we need to set type=button to prevent it behaving as a submit.
                $mform->addElement('static', 'restrictgroupbutton', '',
                    html_writer::tag('button', get_string('restrictbygroup', 'availability'), [
                        'id' => 'restrictbygroup',
                        'type' => 'button',
                        'disabled' => 'disabled',
                        'class' => 'btn btn-secondary',
                        'data-groupavailability' => $groupavailability,
                        'data-groupingavailability' => $groupingavailability
                    ])
                );
            }

            if (!is_siteadmin()){
                $mform->addElement('html', '</div></fieldset>');
            }

            // Availability field. This is just a textarea; the user interface
            // interaction is all implemented in JavaScript.
            $mform->addElement('header', 'availabilityconditionsheader',
                get_string('restrictaccess', 'availability'));
            // Note: This field cannot be named 'availability' because that
            // conflicts with fields in existing modules (such as assign).
            // So it uses a long name that will not conflict.
            $mform->addElement('textarea', 'availabilityconditionsjson',
                get_string('accessrestrictions', 'availability'));
            // The _cm variable may not be a proper cm_info, so get one from modinfo.
            if ($this->_cm) {
                $modinfo = get_fast_modinfo($COURSE);
                $cm = $modinfo->get_cm($this->_cm->id);
            } else {
                $cm = null;
            }
            \core_availability\frontend::include_all_javascript($COURSE, $cm);
            if (!is_siteadmin()){
                $mform->addElement('html', '</div></fieldset> <div style="display:none"><fieldset><div>');
            }
        }

        // Conditional activities: completion tracking section
        if(!isset($completion)) {
            $completion = new completion_info($COURSE);
        }
        if ($completion->is_enabled()) {
            $mform->addElement('header', 'activitycompletionheader', get_string('activitycompletion', 'completion'));
            // Unlock button for if people have completed it (will
            // be removed in definition_after_data if they haven't)
            $mform->addElement('submit', 'unlockcompletion', get_string('unlockcompletion', 'completion'));
            $mform->registerNoSubmitButton('unlockcompletion');
            $mform->addElement('hidden', 'completionunlocked', 0);
            $mform->setType('completionunlocked', PARAM_INT);

            $trackingdefault = COMPLETION_TRACKING_NONE;
            // If system and activity default is on, set it.
            if ($CFG->completiondefault && $this->_features->defaultcompletion) {
                $hasrules = plugin_supports('mod', $this->_modname, FEATURE_COMPLETION_HAS_RULES, true);
                $tracksviews = plugin_supports('mod', $this->_modname, FEATURE_COMPLETION_TRACKS_VIEWS, true);
                if ($hasrules || $tracksviews) {
                    $trackingdefault = COMPLETION_TRACKING_AUTOMATIC;
                } else {
                    $trackingdefault = COMPLETION_TRACKING_MANUAL;
                }
            }

            $mform->addElement('select', 'completion', get_string('completion', 'completion'),
                array(COMPLETION_TRACKING_NONE=>get_string('completion_none', 'completion'),
                      COMPLETION_TRACKING_MANUAL=>get_string('completion_manual', 'completion')));
            $mform->setDefault('completion', $trackingdefault);
            $mform->addHelpButton('completion', 'completion', 'completion');

            // Automatic completion once you view it
            $gotcompletionoptions = false;
            if (plugin_supports('mod', $this->_modname, FEATURE_COMPLETION_TRACKS_VIEWS, false)) {
                $mform->addElement('checkbox', 'completionview', get_string('completionview', 'completion'),
                    get_string('completionview_desc', 'completion'));
                $mform->hideIf('completionview', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                // Check by default if automatic completion tracking is set.
                if ($trackingdefault == COMPLETION_TRACKING_AUTOMATIC) {
                    $mform->setDefault('completionview', 1);
                }
                $gotcompletionoptions = true;
            }

            if (plugin_supports('mod', $this->_modname, FEATURE_GRADE_HAS_GRADE, false)) {
                // This activity supports grading.
                $gotcompletionoptions = true;

                $component = "mod_{$this->_modname}";
                $itemnames = component_gradeitems::get_itemname_mapping_for_component($component);

                if (count($itemnames) === 1) {
                    // Only one gradeitem in this activity.
                    // We use the completionusegrade field here.
                    $mform->addElement(
                        'checkbox',
                        'completionusegrade',
                        get_string('completionusegrade', 'completion'),
                        get_string('completionusegrade_desc', 'completion')
                    );
                    $mform->hideIf('completionusegrade', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                    $mform->addHelpButton('completionusegrade', 'completionusegrade', 'completion');

                    // Complete if the user has reached the pass grade.
                    $mform->addElement(
                        'checkbox',
                        'completionpassgrade', null,
                        get_string('completionpassgrade_desc', 'completion')
                    );
                    $mform->hideIf('completionpassgrade', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                    $mform->disabledIf('completionpassgrade', 'completionusegrade', 'notchecked');
                    $mform->addHelpButton('completionpassgrade', 'completionpassgrade', 'completion');

                    // The disabledIf logic differs between ratings and other grade items due to different field types.
                    if ($this->_features->rating) {
                        // If using the rating system, there is no grade unless ratings are enabled.
                        $mform->disabledIf('completionusegrade', 'assessed', 'eq', 0);
                        $mform->disabledIf('completionpassgrade', 'assessed', 'eq', 0);
                    } else {
                        // All other field types use the '$gradefieldname' field's modgrade_type.
                        $itemnumbers = array_keys($itemnames);
                        $itemnumber = array_shift($itemnumbers);
                        $gradefieldname = component_gradeitems::get_field_name_for_itemnumber($component, $itemnumber, 'grade');
                        $mform->disabledIf('completionusegrade', "{$gradefieldname}[modgrade_type]", 'eq', 'none');
                        $mform->disabledIf('completionpassgrade', "{$gradefieldname}[modgrade_type]", 'eq', 'none');
                    }
                } else if (count($itemnames) > 1) {
                    // There are multiple grade items in this activity.
                    // Show them all.
                    $options = [
                        '' => get_string('activitygradenotrequired', 'completion'),
                    ];
                    foreach ($itemnames as $itemnumber => $itemname) {
                        $options[$itemnumber] = get_string("grade_{$itemname}_name", $component);
                    }

                    $mform->addElement(
                        'select',
                        'completiongradeitemnumber',
                        get_string('completionusegrade', 'completion'),
                        $options
                    );
                    $mform->hideIf('completiongradeitemnumber', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);

                    // Complete if the user has reached the pass grade.
                    $mform->addElement(
                        'checkbox',
                        'completionpassgrade', null,
                        get_string('completionpassgrade_desc', 'completion')
                    );
                    $mform->hideIf('completionpassgrade', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                    $mform->disabledIf('completionpassgrade', 'completiongradeitemnumber', 'eq', '');
                    $mform->addHelpButton('completionpassgrade', 'completionpassgrade', 'completion');
                }
            }

            // Automatic completion according to module-specific rules
            $this->_customcompletionelements = $this->add_completion_rules();
            foreach ($this->_customcompletionelements as $element) {
                $mform->hideIf($element, 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
            }

            $gotcompletionoptions = $gotcompletionoptions ||
                count($this->_customcompletionelements)>0;

            // Automatic option only appears if possible
            if ($gotcompletionoptions) {
                $mform->getElement('completion')->addOption(
                    get_string('completion_automatic', 'completion'),
                    COMPLETION_TRACKING_AUTOMATIC);
            }

            // Completion expected at particular date? (For progress tracking)
            $mform->addElement('date_time_selector', 'completionexpected', get_string('completionexpected', 'completion'),
                array('optional' => true));
            $mform->addHelpButton('completionexpected', 'completionexpected', 'completion');
            $mform->hideIf('completionexpected', 'completion', 'eq', COMPLETION_TRACKING_NONE);
        }

        // Populate module tags.
        if (core_tag_tag::is_enabled('core', 'course_modules')) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'course_modules', 'component' => 'core'));
            if ($this->_cm) {
                $tags = core_tag_tag::get_item_tags_array('core', 'course_modules', $this->_cm->id);
                $mform->setDefault('tags', $tags);
            }
        }

        $this->standard_hidden_coursemodule_elements();

        $this->plugin_extend_coursemodule_standard_elements();
    }
}
