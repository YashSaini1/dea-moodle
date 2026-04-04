<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/edit_form.php');

/**
 * The form for handling editing a course.
 */
class sql_course_edit_form extends course_edit_form {

    const TOPICS_FORMAT = 'topics';

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE;

        $mform    = $this->_form;
        $PAGE->requires->js_call_amd('core_course/formatchooser', 'init');

        $course        = $this->_customdata['course']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];
        $returnurl = $this->_customdata['returnurl'];

        $systemcontext   = context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->course  = $course;
        $this->context = $context;

        // Form definition with new course defaults.
        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->setConstant('returnurl', $returnurl);

        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }

        // Verify permissions to change course category or keep current.
        if (empty($course->id)) {
            $mform->addElement('hidden', 'category', null);
            $mform->setType('category', PARAM_INT);
            $mform->setConstant('category', $category->id);
        } else {
            //keep current
            $mform->addElement('hidden', 'category', null);
            $mform->setType('category', PARAM_INT);
            $mform->setConstant('category', $course->category);
        }

        $choices = array();
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('select', 'visible', get_string('coursevisibility'), $choices);
        $mform->addHelpButton('visible', 'coursevisibility');
        $mform->setDefault('visible', $courseconfig->visible);
        if (!empty($course->id)) {
            if (!has_capability('moodle/course:visibility', $coursecontext)) {
                $mform->hardFreeze('visible');
                $mform->setConstant('visible', $course->visible);
            }
        } else {
            if (!guess_if_creator_will_have_course_capability('moodle/course:visibility', $categorycontext)) {
                $mform->hardFreeze('visible');
                $mform->setConstant('visible', $courseconfig->visible);
            }
        }

        if ($courseconfig->format != static::TOPICS_FORMAT){
            // we need to custom change course format.
            // use default course format and topics here
            $formats = array();
            $formats[$courseconfig->format] = get_string('questions_course', 'theme_sql');
            $formats[static::TOPICS_FORMAT] = get_string('projects_course', 'theme_sql');
            $mform->addElement('select', 'sqlcourse_type', 'Course type', $formats);
            if (!empty($course->id) && $course->format == static::TOPICS_FORMAT){
                $mform->setDefault('sqlcourse_type', static::TOPICS_FORMAT);
            }
        }

        // Download course content.
        if ($CFG->downloadcoursecontentallowed) {
            $downloadchoices = [
                DOWNLOAD_COURSE_CONTENT_DISABLED => get_string('no'),
                DOWNLOAD_COURSE_CONTENT_ENABLED => get_string('yes'),
            ];
            $sitedefaultstring = $downloadchoices[$courseconfig->downloadcontentsitedefault];
            $downloadchoices[DOWNLOAD_COURSE_CONTENT_SITE_DEFAULT] = get_string('sitedefaultspecified', '', $sitedefaultstring);
            $downloadselectdefault = $courseconfig->downloadcontent ?? DOWNLOAD_COURSE_CONTENT_SITE_DEFAULT;

            $mform->addElement('select', 'downloadcontent', get_string('enabledownloadcoursecontent', 'course'), $downloadchoices);
            $mform->addHelpButton('downloadcontent', 'downloadcoursecontent', 'course');
            $mform->setDefault('downloadcontent', $downloadselectdefault);

            if ((!empty($course->id) && !has_capability('moodle/course:configuredownloadcontent', $coursecontext)) ||
                    (empty($course->id) &&
                    !guess_if_creator_will_have_course_capability('moodle/course:configuredownloadcontent', $categorycontext))) {
                $mform->hardFreeze('downloadcontent');
                $mform->setConstant('downloadcontent', $downloadselectdefault);
            }
        }

        $date = (new DateTime())->setTimestamp(usergetmidnight(time()));
        $mform->addElement('hidden', 'startdate', $date->getTimestamp());
        $mform->setType('startdate', PARAM_INT);

        $date->modify('+1 day');
        $mform->addElement('hidden', 'enddate', $date->getTimestamp());
        $mform->setType('enddate', PARAM_INT);

        if (!empty($CFG->enablecourserelativedates)) {
            $attributes = [
                'aria-describedby' => 'relativedatesmode_warning'
            ];
            if (!empty($course->id)) {
                $attributes['disabled'] = true;
            }
            $relativeoptions = [
                0 => get_string('no'),
                1 => get_string('yes'),
            ];
            $relativedatesmodegroup = [];
            $relativedatesmodegroup[] = $mform->createElement('select', 'relativedatesmode', get_string('relativedatesmode'),
                $relativeoptions, $attributes);
            $relativedatesmodegroup[] = $mform->createElement('html', html_writer::span(get_string('relativedatesmode_warning'),
                '', ['id' => 'relativedatesmode_warning']));
            $mform->addGroup($relativedatesmodegroup, 'relativedatesmodegroup', get_string('relativedatesmode'), null, false);
            $mform->addHelpButton('relativedatesmodegroup', 'relativedatesmode');
        }

        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        $mform->setType('idnumber', PARAM_RAW);
        if (!empty($course->id) and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
            $mform->hardFreeze('idnumber');
            $mform->setConstants('idnumber', $course->idnumber);
        }

        // Description.
        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');

        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        $summaryfields = 'summary_editor';

        if ($overviewfilesoptions = course_overviewfiles_options($course)) {
            $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('courseoverviewfiles'), null, $overviewfilesoptions);
            $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
            $summaryfields .= ',overviewfiles_filemanager';
        }

        if (!empty($course->id) and !has_capability('moodle/course:changesummary', $coursecontext)) {
            // Remove the description header it does not contain anything any more.
            $mform->removeElement('descriptionhdr');
            $mform->hardFreeze($summaryfields);
        }

        // Course format.
//        $mform->addElement('header', 'courseformathdr', get_string('type_format', 'plugin'));

//        $courseformats = get_sorted_course_formats(true);
//        $formcourseformats = array();
//        foreach ($courseformats as $courseformat) {
//            $formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
//        }
//        if (isset($course->format)) {
//            $course->format = course_get_format($course)->get_format(); // replace with default if not found
//            if (!in_array($course->format, $courseformats)) {
//                // this format is disabled. Still display it in the dropdown
//                $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
//                        get_string('pluginname', 'format_'.$course->format));
//            }
//        }

        $mform->addElement('hidden', 'format', $courseconfig->format);
        $mform->setType('format', PARAM_TEXT);

        // Just a placeholder for the course format options.
        $mform->addElement('hidden', 'addcourseformatoptionshere');
        $mform->setType('addcourseformatoptionshere', PARAM_BOOL);

        // Appearance.
//        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

//        if (!empty($CFG->allowcoursethemes)) {
//            $themeobjects = get_list_of_themes();
//            $themes=array();
//            $themes[''] = get_string('forceno');
//            foreach ($themeobjects as $key=>$theme) {
//                if (empty($theme->hidefromselector)) {
//                    $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
//                }
//            }
//            $mform->addElement('select', 'theme', get_string('forcetheme'), $themes);
//        }

        if ((empty($course->id) && guess_if_creator_will_have_course_capability('moodle/course:setforcedlanguage', $categorycontext))
                || (!empty($course->id) && has_capability('moodle/course:setforcedlanguage', $coursecontext))) {

            $mform->addElement('hidden', 'lang', $courseconfig->lang);
            $mform->setType('lang', PARAM_RAW);
        }

        // Multi-Calendar Support - see MDL-18375.
        $calendartypes = \core_calendar\type_factory::get_list_of_calendar_types();
        // We do not want to show this option unless there is more than one calendar type to display.
        if (count($calendartypes) > 1) {
            $calendars = array();
            $calendars[''] = get_string('forceno');
            $calendars += $calendartypes;
            $mform->addElement('select', 'calendartype', get_string('forcecalendartype', 'calendar'), $calendars);
        }

        // disable news items
        $mform->addElement('hidden', 'newsitems', 0);
        $mform->setType('newsitems', PARAM_INT);

        $mform->addElement('hidden', 'showgrades', $courseconfig->showgrades);
        $mform->setType('showgrades', PARAM_INT);

        $mform->addElement('hidden', 'showreports', $courseconfig->showreports);
        $mform->setType('showreports', PARAM_INT);

        $mform->addElement('hidden', 'showactivitydates', $courseconfig->showactivitydates);
        $mform->setType('showactivitydates', PARAM_INT);


        // Handle non-existing $course->maxbytes on course creation.
//        $coursemaxbytes = !isset($course->maxbytes) ? null : $course->maxbytes;

        // Let's prepare the maxbytes popup.
//        $choices = get_max_upload_sizes($CFG->maxbytes, 0, 0, $coursemaxbytes);
        $mform->addElement('hidden', 'maxbytes', $courseconfig->maxbytes);
        $mform->setType('maxbytes', PARAM_RAW);

        // Completion tracking.
        if (completion_info::is_enabled_for_site()) {
            $mform->addElement('hidden', 'enablecompletion', $courseconfig->enablecompletion);
            $mform->setType('enablecompletion', PARAM_INT);

            $showcompletionconditions = $courseconfig->showcompletionconditions ?? COMPLETION_SHOW_CONDITIONS;
            $mform->addElement('hidden', 'showcompletionconditions', $showcompletionconditions);
            $mform->setType('showcompletionconditions', PARAM_INT);
        } else {
            $mform->addElement('hidden', 'enablecompletion');
            $mform->setType('enablecompletion', PARAM_INT);
            $mform->setDefault('enablecompletion', 0);
        }

        enrol_course_edit_form($mform, $course, $context);

        $mform->addElement('hidden', 'groupmode', $courseconfig->groupmode);
        $mform->setType('groupmode', PARAM_INT);

        $mform->addElement('hidden', 'groupmodeforce', $courseconfig->groupmodeforce);
        $mform->setType('groupmodeforce', PARAM_INT);

        $mform->addElement('hidden', 'defaultgroupingid', 0);
        $mform->setType('defaultgroupingid', PARAM_INT);

        if (core_tag_tag::is_enabled('core', 'course') &&
                ((empty($course->id) && guess_if_creator_will_have_course_capability('moodle/course:tag', $categorycontext))
                || (!empty($course->id) && has_capability('moodle/course:tag', $coursecontext)))) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'),
                    array('itemtype' => 'course', 'component' => 'core'));
        }

        // Add custom fields to the form.
        $handler = core_course\customfield\course_handler::create();
        $handler->set_parent_context($categorycontext); // For course handler only.
        $handler->instance_form_definition($mform, empty($course->id) ? 0 : $course->id);

        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        if ($returnto !== 0) {
            $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        }
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('savechangesanddisplay'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        // Prepare custom fields data.
        $handler->instance_form_before_set_data($course);
        // Finally set the current form data
        $this->set_data($course);
    }

    /**
     * Fill in the current page data for this course.
     */
    function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        // add available groupings
        $formatvalue = $mform->getElementValue('format');
        $courseid = $mform->getElementValue('id');
        if (!$mform->elementExists('sqlcourse_type')){
            $course_format = $mform->getElementValue('sqlcourse_type');
            if (!empty($course_format) && $course_format[0] == static::TOPICS_FORMAT){
                $formatvalue = static::TOPICS_FORMAT;
            }
        }

        // add course format options
        if (is_array($formatvalue) && !empty($formatvalue)) {
            $params = array('format' => $formatvalue[0]);
            // Load the course as well if it is available, course formats may need it to work out
            // they preferred course end date.
            if ($courseid) {
                $params['id'] = $courseid;
            }
            $courseformat = course_get_format((object)$params);

            $elements = $courseformat->create_edit_form_elements($mform);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($mform->removeElement($elements[$i]->getName(), false),
                        'addcourseformatoptionshere');
            }

            // Remove newsitems element if format does not support news.
            if (!$courseformat->supports_news()) {
                $mform->removeElement('newsitems');
            }
        }

        // Tweak the form with values provided by custom fields in use.
        $handler  = core_course\customfield\course_handler::create();
        $handler->instance_form_definition_after_data($mform, empty($courseid) ? 0 : $courseid);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }

        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber']) && (empty($data['id']) || $this->course->idnumber != $data['idnumber'])) {
            if ($course = $DB->get_record('course', array('idnumber' => $data['idnumber']), '*', IGNORE_MULTIPLE)) {
                if (empty($data['id']) || $course->id != $data['id']) {
                    $errors['idnumber'] = get_string('courseidnumbertaken', 'error', $course->fullname);
                }
            }
        }

        if ($errorcode = course_validate_dates($data)) {
            $errors['enddate'] = get_string($errorcode, 'error');
        }

        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }

        // Add the custom fields validation.
        $handler = core_course\customfield\course_handler::create();
        $errors  = array_merge($errors, $handler->instance_form_validation($data, $files));

        return $errors;
    }

    function get_data(){
        $data = parent::get_data();
        if (empty($data) && empty($data->sqlcourse_type)){
            return $data;
        }

        if ($data->sqlcourse_type == sql_course_edit_form::TOPICS_FORMAT){
            $data->format = sql_course_edit_form::TOPICS_FORMAT;
        } else {
            $data->format = get_config('moodlecourse')->format;
        }

        return $data;
    }

}
