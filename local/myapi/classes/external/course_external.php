<?php

namespace local_myapi\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/url/locallib.php');

class course_external extends \external_api
{
    public static function get_course_full_data_parameters()
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function get_course_full_data($courseid)
    {
        global $DB;

        $params = self::validate_parameters(
            self::get_course_full_data_parameters(),
            ['courseid' => $courseid]
        );

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $modinfo = get_fast_modinfo($params['courseid']);
        $data = [
            'course' => (array) $course,
            'sections' => [],
        ];
        $sectionmap = [];

        $sections = $DB->get_records('course_sections', ['course' => $params['courseid']], 'section ASC', '*');

        foreach ($sections as $section) {
            $sectionmap[$section->id] = count($data['sections']);
            $sectiondata = (array) $section;
            $sectiondata['modules'] = [];
            $data['sections'][] = $sectiondata;
        }

        $coursemodules = $DB->get_records_sql(
            "SELECT cm.*, m.name AS modname
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = :courseid
              ORDER BY cm.section ASC, cm.id ASC",
            [
                'courseid' => $params['courseid'],
            ]
        );

        $modulesbysection = [];

        foreach ($coursemodules as $cm) {
            if (!isset($sectionmap[$cm->section])) {
                continue;
            }

            $modulerecord = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', IGNORE_MISSING);
            $sectionindex = $sectionmap[$cm->section];
            $moduledata = (array) $cm;
            $moduledata['name'] = ($modulerecord && property_exists($modulerecord, 'name')) ? $modulerecord->name : $cm->modname;
            $moduledata['intro'] = ($modulerecord && property_exists($modulerecord, 'intro')) ? $modulerecord->intro : null;
            $moduledata['introformat'] = ($modulerecord && property_exists($modulerecord, 'introformat'))
                ? (int) $modulerecord->introformat
                : null;
            $moduledata['display'] = ($modulerecord && property_exists($modulerecord, 'display')) ? (int) $modulerecord->display : null;
            $moduledata['url'] = null;

            if ($cm->modname === 'url') {
                $urlinstance = $DB->get_record('url', ['id' => $cm->instance], 'externalurl', IGNORE_MISSING);
                if ($urlinstance && !empty($urlinstance->externalurl)) {
                    $moduledata['url'] = $urlinstance->externalurl;
                }
            }

            if ($cm->modname === 'quiz') {
                global $USER;
                $quizdata = [
                    'grade' => null,
                    'attempts' => [],
                ];

                $quizgrade = $DB->get_record('quiz_grades', ['quiz' => $cm->instance, 'userid' => $USER->id], 'grade');
                if ($quizgrade) {
                    $quizdata['grade'] = (string) $quizgrade->grade;
                }

                $attempts = $DB->get_records('quiz_attempts', ['quiz' => $cm->instance, 'userid' => $USER->id], 'attempt ASC', 'id, attempt, state, timestart, timefinish, sumgrades');
                if ($attempts) {
                    foreach ($attempts as $attempt) {
                        $quizdata['attempts'][] = [
                            'id' => (int) $attempt->id,
                            'attempt' => (int) $attempt->attempt,
                            'state' => $attempt->state,
                            'timestart' => (int) $attempt->timestart,
                            'timefinish' => (int) $attempt->timefinish,
                            'sumgrades' => $attempt->sumgrades !== null ? (string) $attempt->sumgrades : null,
                        ];
                    }
                }
                $moduledata['quizdata'] = $quizdata;
            }

            if ($moduledata['url'] === null && isset($modinfo->cms[$cm->id])) {
                $cminfo = $modinfo->cms[$cm->id];
                if (!empty($cminfo->url) && $cm->modname !== 'page' && $cm->modname !== 'label') {
                    $moduledata['url'] = $cminfo->url->out(false);
                }
            }

            if (!isset($modulesbysection[$cm->section])) {
                $modulesbysection[$cm->section] = [];
            }

            $modulesbysection[$cm->section][(int) $cm->id] = $moduledata;
        }

        foreach ($sections as $section) {
            $sectionid = (int) $section->id;
            if (empty($modulesbysection[$sectionid])) {
                continue;
            }

            $orderedmodules = [];
            $remainingmodules = $modulesbysection[$sectionid];
            $sequence = trim((string) $section->sequence);

            if ($sequence !== '') {
                foreach (explode(',', $sequence) as $cmidraw) {
                    $cmid = (int) trim($cmidraw);
                    if ($cmid > 0 && isset($remainingmodules[$cmid])) {
                        $orderedmodules[] = $remainingmodules[$cmid];
                        unset($remainingmodules[$cmid]);
                    }
                }
            }

            foreach ($remainingmodules as $moduledata) {
                $orderedmodules[] = $moduledata;
            }

            $sectionindex = $sectionmap[$sectionid];
            $data['sections'][$sectionindex]['modules'] = $orderedmodules;
        }

        return $data;
    }

    public static function get_course_full_data_returns()
    {
        return new \external_single_structure([
            'course' => self::get_course_structure(),
            'sections' => new \external_multiple_structure(self::get_section_structure()),
        ]);
    }

    protected static function get_course_structure()
    {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT),
            'category' => new \external_value(PARAM_INT),
            'sortorder' => new \external_value(PARAM_INT),
            'fullname' => new \external_value(PARAM_RAW),
            'shortname' => new \external_value(PARAM_RAW),
            'idnumber' => new \external_value(PARAM_RAW),
            'summary' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summaryformat' => new \external_value(PARAM_INT),
            'format' => new \external_value(PARAM_RAW),
            'showgrades' => new \external_value(PARAM_INT),
            'newsitems' => new \external_value(PARAM_INT),
            'startdate' => new \external_value(PARAM_INT),
            'enddate' => new \external_value(PARAM_INT),
            'relativedatesmode' => new \external_value(PARAM_INT),
            'marker' => new \external_value(PARAM_INT),
            'maxbytes' => new \external_value(PARAM_INT),
            'legacyfiles' => new \external_value(PARAM_INT),
            'showreports' => new \external_value(PARAM_INT),
            'visible' => new \external_value(PARAM_INT),
            'visibleold' => new \external_value(PARAM_INT),
            'downloadcontent' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
            'groupmode' => new \external_value(PARAM_INT),
            'groupmodeforce' => new \external_value(PARAM_INT),
            'defaultgroupingid' => new \external_value(PARAM_INT),
            'lang' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'calendartype' => new \external_value(PARAM_RAW),
            'theme' => new \external_value(PARAM_RAW),
            'timecreated' => new \external_value(PARAM_INT),
            'timemodified' => new \external_value(PARAM_INT),
            'requested' => new \external_value(PARAM_INT),
            'enablecompletion' => new \external_value(PARAM_INT),
            'completionnotify' => new \external_value(PARAM_INT),
            'cacherev' => new \external_value(PARAM_INT),
            'originalcourseid' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
            'showactivitydates' => new \external_value(PARAM_INT),
            'showcompletionconditions' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
        ]);
    }

    protected static function get_section_structure()
    {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT),
            'course' => new \external_value(PARAM_INT),
            'section' => new \external_value(PARAM_INT),
            'name' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summary' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summaryformat' => new \external_value(PARAM_INT),
            'sequence' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'visible' => new \external_value(PARAM_INT),
            'availability' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'timemodified' => new \external_value(PARAM_INT),
            'modules' => new \external_multiple_structure(self::get_module_structure()),
        ]);
    }

    protected static function get_module_structure()
    {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT),
            'course' => new \external_value(PARAM_INT),
            'module' => new \external_value(PARAM_INT),
            'instance' => new \external_value(PARAM_INT),
            'section' => new \external_value(PARAM_INT),
            'idnumber' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'added' => new \external_value(PARAM_INT),
            'score' => new \external_value(PARAM_INT),
            'indent' => new \external_value(PARAM_INT),
            'visible' => new \external_value(PARAM_INT),
            'visibleoncoursepage' => new \external_value(PARAM_INT),
            'visibleold' => new \external_value(PARAM_INT),
            'groupmode' => new \external_value(PARAM_INT),
            'groupingid' => new \external_value(PARAM_INT),
            'completion' => new \external_value(PARAM_INT),
            'completiongradeitemnumber' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
            'completionview' => new \external_value(PARAM_INT),
            'completionexpected' => new \external_value(PARAM_INT),
            'completionpassgrade' => new \external_value(PARAM_INT),
            'showdescription' => new \external_value(PARAM_INT),
            'availability' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'deletioninprogress' => new \external_value(PARAM_INT),
            'downloadcontent' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
            'lang' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'modname' => new \external_value(PARAM_RAW),
            'name' => new \external_value(PARAM_RAW),
            'intro' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'introformat' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
            'display' => new \external_value(PARAM_INT, VALUE_OPTIONAL),
            'url' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'quizdata' => new \external_single_structure([
                'grade' => new \external_value(PARAM_RAW, 'Final quiz grade', VALUE_OPTIONAL),
                'attempts' => new \external_multiple_structure(
                    new \external_single_structure([
                        'id' => new \external_value(PARAM_INT, 'Attempt ID'),
                        'attempt' => new \external_value(PARAM_INT, 'Attempt number'),
                        'state' => new \external_value(PARAM_RAW, 'Attempt state'),
                        'timestart' => new \external_value(PARAM_INT, 'Time started'),
                        'timefinish' => new \external_value(PARAM_INT, 'Time finished'),
                        'sumgrades' => new \external_value(PARAM_RAW, 'Sum of grades', VALUE_OPTIONAL),
                    ]), 'Quiz attempts details', VALUE_OPTIONAL
                )
            ], 'Quiz data for student', VALUE_OPTIONAL),
        ]);
    }

    public static function create_sections_parameters()
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'sections' => new \external_multiple_structure(
                new \external_single_structure([
                    'name' => new \external_value(PARAM_TEXT, 'Section name', VALUE_DEFAULT, ''),
                ])
            ),
        ]);
    }

    public static function create_sections($courseid, $sections)
    {
        global $DB;

        $params = self::validate_parameters(
            self::create_sections_parameters(),
            [
                'courseid' => $courseid,
                'sections' => $sections,
            ]
        );

        $course = get_course($params['courseid'], MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        $result = [];

        foreach ($params['sections'] as $section) {
            $sectionrecord = course_create_section($course, 0);
            $sectionrecord->name = trim($section['name']);
            if ($sectionrecord->name === '') {
                $sectionrecord->name = null;
            }
            $sectionrecord->timemodified = time();

            $DB->update_record('course_sections', $sectionrecord);
            rebuild_course_cache($course->id);

            $updatedsection = $DB->get_record('course_sections', ['id' => $sectionrecord->id], '*', MUST_EXIST);

            $result[] = [
                'id' => (int) $updatedsection->id,
                'course' => (int) $updatedsection->course,
                'section' => (int) $updatedsection->section,
                'name' => $updatedsection->name,
                'summary' => $updatedsection->summary,
                'summaryformat' => (int) $updatedsection->summaryformat,
                'sequence' => $updatedsection->sequence,
                'visible' => (int) $updatedsection->visible,
                'availability' => $updatedsection->availability,
                'timemodified' => (int) $updatedsection->timemodified,
            ];
        }

        return $result;
    }

    public static function create_sections_returns()
    {
        return new \external_multiple_structure(
            self::get_section_structure_without_modules()
        );
    }

    protected static function get_section_structure_without_modules()
    {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT),
            'course' => new \external_value(PARAM_INT),
            'section' => new \external_value(PARAM_INT),
            'name' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summary' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summaryformat' => new \external_value(PARAM_INT),
            'sequence' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'visible' => new \external_value(PARAM_INT),
            'availability' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'timemodified' => new \external_value(PARAM_INT),
        ]);
    }

    public static function update_section_parameters()
    {
        return new \external_function_parameters([
            'sectionid' => new \external_value(PARAM_INT, 'Course section ID'),
            'name' => new \external_value(PARAM_TEXT, 'Section name', VALUE_OPTIONAL),
            'summary' => new \external_value(PARAM_RAW, 'Section summary', VALUE_OPTIONAL),
            'summaryformat' => new \external_value(PARAM_INT, 'Summary format', VALUE_OPTIONAL),
            'visible' => new \external_value(PARAM_INT, 'Whether the section is visible', VALUE_OPTIONAL),
            'availability' => new \external_value(PARAM_RAW, 'Availability JSON for the section', VALUE_OPTIONAL),
            'fields' => new \external_value(PARAM_RAW, 'Optional JSON object with extra section fields', VALUE_OPTIONAL),
        ]);
    }

    public static function update_section($sectionid, $name = null, $summary = null, $summaryformat = null, $visible = null, $availability = null, $fields = null)
    {
        global $CFG, $DB;

        $incoming = ['sectionid' => $sectionid];
        if ($name !== null) {
            $incoming['name'] = $name;
        }
        if ($summary !== null) {
            $incoming['summary'] = $summary;
        }
        if ($summaryformat !== null) {
            $incoming['summaryformat'] = $summaryformat;
        }
        if ($visible !== null) {
            $incoming['visible'] = $visible;
        }
        if ($availability !== null) {
            $incoming['availability'] = $availability;
        }
        if ($fields !== null) {
            $incoming['fields'] = $fields;
        }

        $params = self::validate_parameters(
            self::update_section_parameters(),
            $incoming
        );

        $section = $DB->get_record('course_sections', ['id' => $params['sectionid']], '*', MUST_EXIST);
        $course = get_course($section->course, MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);

        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        $data = [];

        if (array_key_exists('name', $params)) {
            $data['name'] = trim($params['name']);
            if ($data['name'] === '') {
                $data['name'] = null;
            }
        }
        if (array_key_exists('summary', $params)) {
            $data['summary'] = $params['summary'];
        }
        if (array_key_exists('summaryformat', $params)) {
            $data['summaryformat'] = (int) $params['summaryformat'];
        }
        if (array_key_exists('visible', $params)) {
            $data['visible'] = (int) $params['visible'];
        }
        if (array_key_exists('availability', $params)) {
            if (!empty($CFG->enableavailability)) {
                if ($params['availability'] === '' || $params['availability'] === null) {
                    $data['availability'] = null;
                } else {
                    $decodedavailability = json_decode($params['availability']);
                    if (json_last_error() !== JSON_ERROR_NONE || $decodedavailability === null) {
                        throw new \invalid_parameter_exception('availability must be valid JSON');
                    }

                    try {
                        $tree = new \core_availability\tree($decodedavailability);
                    } catch (\Throwable $exception) {
                        throw new \invalid_parameter_exception('availability must be valid JSON');
                    }

                    $data['availability'] = $tree->is_empty() ? null : $params['availability'];
                }
            } else {
                throw new \moodle_exception('availability is not enabled on this site');
            }
        }

        if (array_key_exists('fields', $params)) {
            $extra = json_decode($params['fields'], true);
            if (!is_array($extra)) {
                throw new \invalid_parameter_exception('fields must be a valid JSON object');
            }

            foreach ($extra as $key => $value) {
                if (!is_string($key) || $key === '') {
                    continue;
                }
                if (in_array($key, ['id', 'course', 'section', 'sequence'], true)) {
                    continue;
                }
                $data[$key] = $value;
            }
        }

        course_update_section($course, $section, $data);

        $updatedsection = $DB->get_record('course_sections', ['id' => $section->id], '*', MUST_EXIST);

        return [
            'sectionid' => (int) $updatedsection->id,
            'courseid' => (int) $updatedsection->course,
            'section' => (int) $updatedsection->section,
            'name' => $updatedsection->name,
            'summary' => $updatedsection->summary,
            'summaryformat' => (int) $updatedsection->summaryformat,
            'visible' => (int) $updatedsection->visible,
            'availability' => $updatedsection->availability,
            'timemodified' => (int) $updatedsection->timemodified,
        ];
    }

    public static function update_section_returns()
    {
        return new \external_single_structure([
            'sectionid' => new \external_value(PARAM_INT),
            'courseid' => new \external_value(PARAM_INT),
            'section' => new \external_value(PARAM_INT),
            'name' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summary' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'summaryformat' => new \external_value(PARAM_INT),
            'visible' => new \external_value(PARAM_INT),
            'availability' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
            'timemodified' => new \external_value(PARAM_INT),
        ]);
    }

    public static function delete_section_parameters()
    {
        return new \external_function_parameters([
            'sectionid' => new \external_value(PARAM_INT, 'Course section ID'),
        ]);
    }

    public static function delete_section($sectionid)
    {
        global $DB;

        $params = self::validate_parameters(
            self::delete_section_parameters(),
            ['sectionid' => $sectionid]
        );

        $section = $DB->get_record('course_sections', ['id' => $params['sectionid']], '*', MUST_EXIST);
        $course = get_course($section->course, MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);

        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        $deleted = course_delete_section($course, $section->section, true, false);
        if (!$deleted) {
            throw new \moodle_exception('unabletodeletesection', 'error');
        }

        return [
            'sectionid' => (int) $section->id,
            'courseid' => (int) $course->id,
            'section' => (int) $section->section,
            'deleted' => true,
        ];
    }

    public static function delete_section_returns()
    {
        return new \external_single_structure([
            'sectionid' => new \external_value(PARAM_INT),
            'courseid' => new \external_value(PARAM_INT),
            'section' => new \external_value(PARAM_INT),
            'deleted' => new \external_value(PARAM_BOOL),
        ]);
    }

    public static function create_module_parameters()
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new \external_value(PARAM_INT, 'Course section ID'),
            'modulename' => new \external_value(PARAM_ALPHANUMEXT, 'Module name, for example page, forum, assign'),
            'name' => new \external_value(PARAM_TEXT, 'Module name'),
            'intro' => new \external_value(PARAM_RAW, 'Module intro text', VALUE_DEFAULT, ''),
            'introformat' => new \external_value(PARAM_INT, 'Intro format', VALUE_DEFAULT, FORMAT_HTML),
            'visible' => new \external_value(PARAM_INT, 'Whether the module is visible', VALUE_DEFAULT, 1),
            'showdescription' => new \external_value(PARAM_INT, 'Whether to show intro on course page', VALUE_DEFAULT, 0),
            'fields' => new \external_value(PARAM_RAW, 'Optional JSON object with extra module fields', VALUE_DEFAULT, '{}'),
        ]);
    }

    public static function create_module($courseid, $sectionid, $modulename, $name, $intro = '', $introformat = FORMAT_HTML, $visible = 1, $showdescription = 0, $fields = '{}')
    {
        global $DB;

        $params = self::validate_parameters(
            self::create_module_parameters(),
            [
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'modulename' => $modulename,
                'name' => $name,
                'intro' => $intro,
                'introformat' => $introformat,
                'visible' => $visible,
                'showdescription' => $showdescription,
                'fields' => $fields,
            ]
        );

        $course = get_course($params['courseid'], MUST_EXIST);
        $sectionrecord = $DB->get_record('course_sections', [
            'id' => $params['sectionid'],
            'course' => $course->id,
        ], '*', MUST_EXIST);

        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = $params['modulename'];
        $moduleinfo->course = $course->id;
        $moduleinfo->section = (int) $sectionrecord->section;
        $moduleinfo->name = $params['name'];
        $moduleinfo->visible = (int) $params['visible'];
        $moduleinfo->showdescription = (int) $params['showdescription'];
        $moduleinfo->introeditor = [
            'text' => $params['intro'],
            'format' => $params['introformat'],
            'itemid' => 0,
        ];

        $extra = json_decode($params['fields'], true);
        if (!is_array($extra)) {
            throw new \invalid_parameter_exception('fields must be a valid JSON object');
        }

        foreach ($extra as $key => $value) {
            if (is_string($key) && $key !== '') {
                $moduleinfo->{$key} = $value;
            }
        }

        if ($moduleinfo->modulename === 'page') {
            if (!isset($moduleinfo->content)) {
                $moduleinfo->content = $params['intro'];
            }
            if (!isset($moduleinfo->contentformat)) {
                $moduleinfo->contentformat = $params['introformat'];
            }
        }

        $createdmodule = create_module($moduleinfo);
        rebuild_course_cache($course->id);

        $cm = get_coursemodule_from_id('', $createdmodule->coursemodule, 0, false, MUST_EXIST);
        $moduleinstance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
        $modinfo = get_fast_modinfo($course->id);

        $moduledata = [
            'id' => (int) $cm->id,
            'course' => (int) $cm->course,
            'module' => (int) $cm->module,
            'instance' => (int) $cm->instance,
            'section' => (int) $cm->section,
            'idnumber' => $cm->idnumber,
            'added' => (int) $cm->added,
            'score' => (int) $cm->score,
            'indent' => (int) $cm->indent,
            'visible' => (int) $cm->visible,
            'visibleoncoursepage' => (int) $cm->visibleoncoursepage,
            'visibleold' => (int) $cm->visibleold,
            'groupmode' => (int) $cm->groupmode,
            'groupingid' => (int) $cm->groupingid,
            'completion' => (int) $cm->completion,
            'completiongradeitemnumber' => $cm->completiongradeitemnumber,
            'completionview' => (int) $cm->completionview,
            'completionexpected' => (int) $cm->completionexpected,
            'completionpassgrade' => (int) $cm->completionpassgrade,
            'showdescription' => (int) $cm->showdescription,
            'availability' => $cm->availability,
            'deletioninprogress' => (int) $cm->deletioninprogress,
            'downloadcontent' => $cm->downloadcontent,
            'lang' => $cm->lang,
            'modname' => $cm->modname,
            'name' => $moduleinstance->name,
            'intro' => property_exists($moduleinstance, 'intro') ? $moduleinstance->intro : null,
            'introformat' => property_exists($moduleinstance, 'introformat') ? (int) $moduleinstance->introformat : null,
            'display' => property_exists($moduleinstance, 'display') ? (int) $moduleinstance->display : null,
            'url' => null,
        ];

        if ($cm->modname === 'url') {
            $urlinstance = $DB->get_record('url', ['id' => $cm->instance], 'externalurl', IGNORE_MISSING);
            if ($urlinstance && !empty($urlinstance->externalurl)) {
                $moduledata['url'] = $urlinstance->externalurl;
            }
        }

        if ($moduledata['url'] === null && isset($modinfo->cms[$cm->id])) {
            $cminfo = $modinfo->cms[$cm->id];
            if (!empty($cminfo->url) && $cm->modname !== 'page' && $cm->modname !== 'label') {
                $moduledata['url'] = $cminfo->url->out(false);
            }
        }

        return $moduledata;
    }

    public static function create_module_returns()
    {
        return self::get_module_structure();
    }

    public static function update_module_parameters()
    {
        return new \external_function_parameters([
            'coursemoduleid' => new \external_value(PARAM_INT, 'Course module ID'),
            'name' => new \external_value(PARAM_TEXT, 'Module name', VALUE_OPTIONAL),
            'visible' => new \external_value(PARAM_INT, 'Whether the module is visible', VALUE_OPTIONAL),
            'visibleoncoursepage' => new \external_value(PARAM_INT, 'Whether the module is shown on the course page', VALUE_OPTIONAL),
            'showdescription' => new \external_value(PARAM_INT, 'Whether to show intro on course page', VALUE_OPTIONAL),
            'availability' => new \external_value(PARAM_RAW, 'Availability JSON for the module', VALUE_OPTIONAL),
            'fields' => new \external_value(PARAM_RAW, 'Optional JSON object with extra module fields', VALUE_OPTIONAL),
        ]);
    }

    public static function update_module($coursemoduleid, $name = null, $visible = null, $visibleoncoursepage = null, $availability = null, $fields = null, $showdescription = null)
    {
        global $CFG, $DB;

        // Normalize empty strings to null for optional parameters to prevent validation errors
        if ($name === '') {
            $name = null;
        }
        if ($visible === '') {
            $visible = null;
        }
        if ($visibleoncoursepage === '') {
            $visibleoncoursepage = null;
        }
        if ($availability === '') {
            $availability = null;
        }
        if ($fields === '') {
            $fields = null;
        }
        if ($showdescription === '') {
            $showdescription = null;
        }

        $isjsonobject = static function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $trimmed = trim($value);
            if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
                return false;
            }

            json_decode($trimmed, true);
            return json_last_error() === JSON_ERROR_NONE;
        };

        if ($fields === null && $availability === null && $visibleoncoursepage === null && $isjsonobject($visible)) {
            $fields = $visible;
            $visible = null;
        }

        if ($fields === null && $visible === null && $availability === null && $isjsonobject($name)) {
            $fields = $name;
            $name = null;
        }

        $incoming = ['coursemoduleid' => $coursemoduleid];
        if ($name !== null) {
            $incoming['name'] = $name;
        }
        if ($visible !== null) {
            $incoming['visible'] = $visible;
        }
        if ($visibleoncoursepage !== null) {
            $incoming['visibleoncoursepage'] = $visibleoncoursepage;
        }
        if ($showdescription !== null) {
            $incoming['showdescription'] = $showdescription;
        }
        if ($availability !== null) {
            $incoming['availability'] = $availability;
        }
        if ($fields !== null) {
            $incoming['fields'] = $fields;
        }

        $params = self::validate_parameters(
            self::update_module_parameters(),
            $incoming
        );

        $cm = get_coursemodule_from_id('', $params['coursemoduleid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course, MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        $modcontext = \context_module::instance($cm->id);

        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        $updatemodule = false;

        if (array_key_exists('name', $params)) {
            $moduleinstance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
            $moduleinstance->name = trim($params['name']);
            if ($moduleinstance->name === '') {
                throw new \invalid_parameter_exception('name cannot be empty');
            }
            $moduleinstance->id = $cm->instance;
            $DB->update_record($cm->modname, $moduleinstance);
            $updatemodule = true;
        }

        if (array_key_exists('fields', $params)) {
            $extra = json_decode($params['fields'], true);
            if (!is_array($extra)) {
                throw new \invalid_parameter_exception('fields must be a valid JSON object');
            }

            if (array_key_exists('sectionid', $extra)) {
                $targetsection = $DB->get_record('course_sections', [
                    'id' => (int) $extra['sectionid'],
                    'course' => $course->id,
                ], '*', MUST_EXIST);

                $currentsection = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
                if ((int) $targetsection->id !== (int) $currentsection->id) {
                    $modcontext = \context_module::instance($cm->id);
                    require_capability('moodle/course:manageactivities', $coursecontext);
                    require_capability('moodle/course:manageactivities', $modcontext);
                    moveto_module($cm, $targetsection);
                    $updatemodule = true;
                    $cm = get_coursemodule_from_id('', $cm->id, 0, false, MUST_EXIST);
                    $modcontext = \context_module::instance($cm->id);
                }
                unset($extra['sectionid']);
            }

            if ($cm->modname === 'url') {
                $moduleinstance = $DB->get_record('url', ['id' => $cm->instance], '*', MUST_EXIST);

                foreach ($extra as $key => $value) {
                    if (is_string($key) && $key !== '' && property_exists($moduleinstance, $key)) {
                        $moduleinstance->{$key} = $value;
                    }
                }

                if (property_exists($moduleinstance, 'externalurl') && array_key_exists('externalurl', $extra)) {
                    $moduleinstance->externalurl = url_fix_submitted_url($extra['externalurl']);
                }

                $moduleinstance->timemodified = time();
                $DB->update_record('url', $moduleinstance);
                $updatemodule = true;
            } else {
                $moduleinstance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);

                foreach ($extra as $key => $value) {
                    if (is_string($key) && $key !== '' && property_exists($moduleinstance, $key)) {
                        $moduleinstance->{$key} = $value;
                    }
                }

                if (property_exists($moduleinstance, 'timemodified')) {
                    $moduleinstance->timemodified = time();
                }

                $moduleinstance->id = $cm->instance;
                $DB->update_record($cm->modname, $moduleinstance);
                $updatemodule = true;
            }
        }

        if (array_key_exists('visible', $params) || array_key_exists('visibleoncoursepage', $params)) {
            require_capability('moodle/course:activityvisibility', $modcontext);

            $newvisible = array_key_exists('visible', $params) ? (int) $params['visible'] : (int) $cm->visible;
            $newvisibleoncoursepage = array_key_exists('visibleoncoursepage', $params)
                ? (int) $params['visibleoncoursepage']
                : (int) $cm->visibleoncoursepage;

            set_coursemodule_visible($cm->id, $newvisible, $newvisibleoncoursepage);
        }

        if (array_key_exists('showdescription', $params)) {
            require_capability('moodle/course:activityvisibility', $modcontext);
            $DB->set_field('course_modules', 'showdescription', (int) $params['showdescription'], ['id' => $cm->id]);
            $updatemodule = true;
        }

        if (array_key_exists('availability', $params)) {
            if (!empty($CFG->enableavailability)) {
                if ($params['availability'] === '' || $params['availability'] === null) {
                    $DB->set_field('course_modules', 'availability', null, ['id' => $cm->id]);
                } else {
                    $decodedavailability = json_decode($params['availability']);
                    if (json_last_error() !== JSON_ERROR_NONE || $decodedavailability === null) {
                        throw new \invalid_parameter_exception('availability must be valid JSON');
                    }

                    try {
                        $tree = new \core_availability\tree($decodedavailability);
                    } catch (\Throwable $exception) {
                        throw new \invalid_parameter_exception('availability must be valid JSON');
                    }

                    $DB->set_field('course_modules', 'availability', $tree->is_empty() ? null : $params['availability'], ['id' => $cm->id]);
                }
                $updatemodule = true;
            } else {
                throw new \moodle_exception('availability is not enabled on this site');
            }
        }

        if ($updatemodule) {
            rebuild_course_cache($course->id, true);
        }

        $updatedcm = get_coursemodule_from_id('', $cm->id, 0, false, MUST_EXIST);
        $moduleinstance = $DB->get_record($updatedcm->modname, ['id' => $updatedcm->instance], '*', MUST_EXIST);
        $modinfo = get_fast_modinfo($course->id);

        $moduledata = [
            'id' => (int) $updatedcm->id,
            'course' => (int) $updatedcm->course,
            'module' => (int) $updatedcm->module,
            'instance' => (int) $updatedcm->instance,
            'section' => (int) $updatedcm->section,
            'idnumber' => $updatedcm->idnumber,
            'added' => (int) $updatedcm->added,
            'score' => (int) $updatedcm->score,
            'indent' => (int) $updatedcm->indent,
            'visible' => (int) $updatedcm->visible,
            'visibleoncoursepage' => (int) $updatedcm->visibleoncoursepage,
            'visibleold' => (int) $updatedcm->visibleold,
            'groupmode' => (int) $updatedcm->groupmode,
            'groupingid' => (int) $updatedcm->groupingid,
            'completion' => (int) $updatedcm->completion,
            'completiongradeitemnumber' => $updatedcm->completiongradeitemnumber,
            'completionview' => (int) $updatedcm->completionview,
            'completionexpected' => (int) $updatedcm->completionexpected,
            'completionpassgrade' => (int) $updatedcm->completionpassgrade,
            'showdescription' => (int) $updatedcm->showdescription,
            'availability' => $updatedcm->availability,
            'deletioninprogress' => (int) $updatedcm->deletioninprogress,
            'downloadcontent' => $updatedcm->downloadcontent,
            'lang' => $updatedcm->lang,
            'modname' => $updatedcm->modname,
            'name' => $moduleinstance->name,
            'intro' => property_exists($moduleinstance, 'intro') ? $moduleinstance->intro : null,
            'introformat' => property_exists($moduleinstance, 'introformat') ? (int) $moduleinstance->introformat : null,
            'display' => property_exists($moduleinstance, 'display') ? (int) $moduleinstance->display : null,
            'url' => null,
        ];

        if ($updatedcm->modname === 'url') {
            $urlinstance = $DB->get_record('url', ['id' => $updatedcm->instance], 'externalurl', IGNORE_MISSING);
            if ($urlinstance && !empty($urlinstance->externalurl)) {
                $moduledata['url'] = $urlinstance->externalurl;
            }
        }

        if ($moduledata['url'] === null && isset($modinfo->cms[$updatedcm->id])) {
            $cminfo = $modinfo->cms[$updatedcm->id];
            if (!empty($cminfo->url)) {
                $moduledata['url'] = $cminfo->url->out(false);
            }
        }

        return $moduledata;
    }

    public static function update_module_returns()
    {
        return self::get_module_structure();
    }

    public static function save_course_image_parameters()
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'draftitemid' => new \external_value(PARAM_INT, 'Draft item ID of the uploaded image'),
        ]);
    }

    public static function save_course_image($courseid, $draftitemid)
    {
        global $CFG, $DB;

        require_once($CFG->libdir . '/filelib.php');

        $params = self::validate_parameters(
            self::save_course_image_parameters(),
            [
                'courseid' => $courseid,
                'draftitemid' => $draftitemid,
            ]
        );

        $course = get_course($params['courseid'], MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);

        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        // Move the image from draft area to course overview files
        $options = [
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['image'],
        ];

        file_save_draft_area_files(
            $params['draftitemid'],
            $coursecontext->id,
            'course',
            'overviewfiles',
            0,
            $options
        );

        // Fix mimetype mismatch if file exists but mimetype doesn't match actual content
        self::fix_overview_file_mimetypes($coursecontext->id);

        rebuild_course_cache($course->id, true);
        \cache::make('core', 'course_image')->delete($course->id);

        return [
            'success' => true,
            'message' => 'Course image saved successfully',
            'courseid' => (int) $course->id,
        ];
    }

    /**
     * Fix mimetype mismatch in overview files by detecting actual file type with GD
     *
     * @param int $contextid The context ID where overview files are stored
     * @return void
     */
    protected static function fix_overview_file_mimetypes($contextid)
    {
        global $DB;

        // Get all image files in overviewfiles for this context (excluding directory entries)
        $files = $DB->get_records_select(
            'files',
            'contextid = ? AND component = ? AND filearea = ? AND itemid = 0 AND filename <> ?',
            [$contextid, 'course', 'overviewfiles', '.'],
            'id ASC'
        );

        $fs = get_file_storage();

        foreach ($files as $filerecord) {
            $storedfile = $fs->get_file_by_id($filerecord->id);
            if (!$storedfile) {
                continue;
            }

            // Get the actual mimetype from GD
            $imageinfo = $storedfile->get_imageinfo();
            if (!$imageinfo || empty($imageinfo['mimetype'])) {
                continue;
            }

            $actualmimetype = $imageinfo['mimetype'];

            // If stored mimetype doesn't match actual mimetype, update it
            if ($storedfile->get_mimetype() !== $actualmimetype) {
                $DB->update_record('files', (object) [
                    'id' => $filerecord->id,
                    'mimetype' => $actualmimetype,
                ]);
            }
        }
    }

    public static function duplicate_module_parameters()
    {
        return new \external_function_parameters([
            'sectionid' => new \external_value(PARAM_INT, 'The section ID to copy the module to'),
            'referencemoduleid' => new \external_value(PARAM_INT, 'The course module ID of the original module to copy'),
        ]);
    }

    public static function duplicate_module($sectionid, $referencemoduleid)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(
            self::duplicate_module_parameters(),
            [
                'sectionid' => $sectionid,
                'referencemoduleid' => $referencemoduleid,
            ]
        );

        $cm = get_coursemodule_from_id('', $params['referencemoduleid'], 0, false, MUST_EXIST);
        $sourcecourse = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        $targetsection = $DB->get_record('course_sections', [
            'id' => $params['sectionid'],
        ], '*', MUST_EXIST);
        $targetcourse = $DB->get_record('course', ['id' => $targetsection->course], '*', MUST_EXIST);

        // Security check: must have manageactivities capability in source module context and target course context
        $sourcecontext = \context_module::instance($cm->id);
        $targetcontext = \context_course::instance($targetcourse->id);

        self::validate_context($sourcecontext);
        self::validate_context($targetcontext);

        require_capability('moodle/course:manageactivities', $sourcecontext);
        require_capability('moodle/course:manageactivities', $targetcontext);

        $newcm = null;

        if ((int) $sourcecourse->id === (int) $targetcourse->id) {
            // Same course duplication - use Moodle's built-in standard method
            $newcm = duplicate_module($sourcecourse, $cm);
            if (!$newcm) {
                throw new \moodle_exception('unabletoduplicate', 'error');
            }
            // Set name to original name (without " copy")
            $DB->set_field($cm->modname, 'name', $cm->name, ['id' => $newcm->instance]);

            if ((int) $newcm->section !== (int) $targetsection->id) {
                moveto_module($newcm, $targetsection);
                $newcm = get_coursemodule_from_id('', $newcm->id, 0, false, MUST_EXIST);
            }
            rebuild_course_cache($sourcecourse->id, true);
        } else {
            // Cross-course duplication using backup and restore controllers
            global $USER;
            require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
            require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
            require_once($CFG->libdir . '/filelib.php');

            $cmcontext = \context_module::instance($cm->id);

            // 1. Backup the original module
            $bc = new \backup_controller(\backup::TYPE_1ACTIVITY, $cm->id, \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $USER->id);
            $backupid = $bc->get_backupid();
            $backupbasepath = $bc->get_plan()->get_basepath();
            $bc->execute_plan();
            $bc->destroy();

            // 2. Restore the module into the target course
            $rc = new \restore_controller($backupid, $targetcourse->id,
                \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $USER->id, \backup::TARGET_CURRENT_ADDING);

            $plan = $rc->get_plan();
            $groupsetting = $plan->get_setting('groups');
            if (empty($groupsetting->get_value())) {
                $groupsetting->set_value(true);
            }

            if (!$rc->execute_precheck()) {
                $precheckresults = $rc->get_precheck_results();
                if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                    if (empty($CFG->keeptempdirectoriesonbackup)) {
                        fulldelete($backupbasepath);
                    }
                    throw new \moodle_exception('precheckfailed', 'error');
                }
            }

            $rc->execute_plan();

            $newcmid = null;
            $tasks = $rc->get_plan()->get_tasks();
            foreach ($tasks as $task) {
                if (is_subclass_of($task, 'restore_activity_task')) {
                    if ($task->get_old_contextid() == $cmcontext->id) {
                        $newcmid = $task->get_moduleid();
                        break;
                    }
                }
            }

            $rc->destroy();

            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($backupbasepath);
            }

            if (!$newcmid) {
                throw new \moodle_exception('unabletoduplicate', 'error');
            }

            // Get new module object from the target course
            $newcm = get_coursemodule_from_id($cm->modname, $newcmid, $targetcourse->id);

            // Keep the original name instead of renaming to indicate it is a copy
            $DB->set_field($cm->modname, 'name', $cm->name, ['id' => $newcm->instance]);

            // Move the activity to our target section
            moveto_module($newcm, $targetsection);

            // Reload the newly moved module
            $newcm = get_coursemodule_from_id('', $newcm->id, 0, false, MUST_EXIST);

            rebuild_course_cache($targetcourse->id, true);
        }

        $moduleinstance = $DB->get_record($newcm->modname, ['id' => $newcm->instance], '*', MUST_EXIST);

        $moduledata = [
            'id' => (int) $newcm->id,
            'course' => (int) $newcm->course,
            'module' => (int) $newcm->module,
            'instance' => (int) $newcm->instance,
            'section' => (int) $newcm->section,
            'idnumber' => $newcm->idnumber,
            'added' => (int) $newcm->added,
            'score' => (int) $newcm->score,
            'indent' => (int) $newcm->indent,
            'visible' => (int) $newcm->visible,
            'visibleoncoursepage' => (int) $newcm->visibleoncoursepage,
            'visibleold' => (int) $newcm->visibleold,
            'groupmode' => (int) $newcm->groupmode,
            'groupingid' => (int) $newcm->groupingid,
            'completion' => (int) $newcm->completion,
            'completiongradeitemnumber' => $newcm->completiongradeitemnumber,
            'completionview' => (int) $newcm->completionview,
            'completionexpected' => (int) $newcm->completionexpected,
            'completionpassgrade' => (int) $newcm->completionpassgrade,
            'showdescription' => (int) $newcm->showdescription,
            'availability' => $newcm->availability,
            'deletioninprogress' => (int) $newcm->deletioninprogress,
            'downloadcontent' => $newcm->downloadcontent,
            'lang' => $newcm->lang,
            'modname' => $newcm->modname,
            'name' => $moduleinstance->name,
            'intro' => property_exists($moduleinstance, 'intro') ? $moduleinstance->intro : null,
            'introformat' => property_exists($moduleinstance, 'introformat') ? (int) $moduleinstance->introformat : null,
            'url' => null,
        ];

        if ($newcm->modname === 'url') {
            $urlinstance = $DB->get_record('url', ['id' => $newcm->instance], 'externalurl', IGNORE_MISSING);
            if ($urlinstance && !empty($urlinstance->externalurl)) {
                $moduledata['url'] = $urlinstance->externalurl;
            }
        }

        return $moduledata;
    }

    public static function duplicate_module_returns()
    {
        return self::get_module_structure();
    }

    public static function save_course_image_returns()
    {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Whether the image was saved successfully'),
            'message' => new \external_value(PARAM_RAW, 'Status message'),
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }
}
