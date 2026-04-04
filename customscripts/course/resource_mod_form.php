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

require_once($CFG->dirroot . '/mod/resource/mod_form.php');

class sql_mod_resource_mod_form extends \mod_resource_mod_form {

    public function __construct($current, $section, $cm, $course) {
        $this->_modname = 'resource';
        parent::__construct($current, $section, $cm, $course);
    }

    public function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        $config = get_config('resource');
        $this->_features->showdescription = false;

        if ($this->current->instance and $this->current->tobemigrated) {
            // resource not migrated yet
            $resource_old = $DB->get_record('resource_old', array('oldid'=>$this->current->instance));
            $mform->addElement('static', 'warning', '', get_string('notmigrated', 'resource', $resource_old->type));
            $mform->addElement('cancel');
            $this->standard_hidden_coursemodule_elements();
            return;
        }

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);
        $filemanager_options = array();
        $filemanager_options['accepted_types'] = '*';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = -1;
        $filemanager_options['mainfile'] = true;

        $mform->addElement('filemanager', 'files', get_string('selectfiles'), null, $filemanager_options);

        // add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'resource'),
                             RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'resource'));
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'resource'), $options);
        }

        if (!is_siteadmin()){
            $mform->addElement('html', '</div></fieldset> <div style="display:none"><fieldset><div>');
        }
        //-------------------------------------------------------
        $mform->addElement('header', 'optionssection', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }

        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'resource'), $options);
            $mform->setDefault('display', $config->display);
            $mform->addHelpButton('display', 'displayselect', 'resource');
        }

        $mform->addElement('checkbox', 'showsize', get_string('showsize', 'resource'));
        $mform->setDefault('showsize', $config->showsize);
        $mform->addHelpButton('showsize', 'showsize', 'resource');
        $mform->addElement('checkbox', 'showtype', get_string('showtype', 'resource'));
        $mform->setDefault('showtype', $config->showtype);
        $mform->addHelpButton('showtype', 'showtype', 'resource');
        $mform->addElement('checkbox', 'showdate', get_string('showdate', 'resource'));
        $mform->setDefault('showdate', $config->showdate);
        $mform->addHelpButton('showdate', 'showdate', 'resource');

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'resource'), array('size'=>3));
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);
            $mform->setAdvanced('popupwidth', true);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'resource'), array('size'=>3));
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
            $mform->setAdvanced('popupheight', true);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_AUTO, $options) or
            array_key_exists(RESOURCELIB_DISPLAY_EMBED, $options) or
            array_key_exists(RESOURCELIB_DISPLAY_FRAME, $options)) {
            $mform->addElement('checkbox', 'printintro', get_string('printintro', 'resource'));
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_POPUP);
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_DOWNLOAD);
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_OPEN);
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_NEW);
            $mform->setDefault('printintro', $config->printintro);
        }

        $options = array('0' => get_string('none'), '1' => get_string('allfiles'), '2' => get_string('htmlfilesonly'));
        $mform->addElement('select', 'filterfiles', get_string('filterfiles', 'resource'), $options);
        $mform->setDefault('filterfiles', $config->filterfiles);
        $mform->setAdvanced('filterfiles', true);

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();
        if (!is_siteadmin()){
            $mform->addElement('html', '</div></fieldset></div><fieldset>');
        }
        //-------------------------------------------------------
        $this->add_action_buttons();

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
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
