<?php

namespace local_myapi\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');

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

        $data = [];
        $sectionmap = [];

        $sections = $DB->get_records('course_sections', ['course' => $params['courseid']], 'section ASC', 'id, course, section, name');

        foreach ($sections as $section) {
            $sectionmap[$section->id] = $section->section;
            $data[$section->section] = [
                'id' => $section->id,
                'name' => $section->name ?: 'Section ' . $section->section,
                'section' => $section->section,
                'modules' => [],
            ];
        }

        $coursemodules = $DB->get_records_sql(
            "SELECT cm.id, cm.visible, cm.section, cm.instance, m.name AS modname
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = :courseid
              ORDER BY cm.id ASC",
            [
                'courseid' => $params['courseid'],
            ]
        );

        foreach ($coursemodules as $cm) {
            if (!isset($sectionmap[$cm->section])) {
                continue;
            }

            $modulename = $DB->get_field($cm->modname, 'name', ['id' => $cm->instance], IGNORE_MISSING);
            $sectionnum = $sectionmap[$cm->section];

            $data[$sectionnum]['modules'][] = [
                'id' => (int) $cm->id,
                'name' => $modulename !== false ? $modulename : $cm->modname,
                'modname' => $cm->modname,
                'url' => null,
                'visible' => (int) $cm->visible,
            ];
        }

        return array_values($data);
    }

    public static function get_course_full_data_returns()
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT),
                'name' => new \external_value(PARAM_TEXT),
                'section' => new \external_value(PARAM_INT),
                'modules' => new \external_multiple_structure(
                    new \external_single_structure([
                        'id' => new \external_value(PARAM_INT),
                        'name' => new \external_value(PARAM_TEXT),
                        'modname' => new \external_value(PARAM_TEXT),
                        'url' => new \external_value(PARAM_TEXT, VALUE_OPTIONAL),
                        'visible' => new \external_value(PARAM_INT),
                    ])
                ),
            ])
        );
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

            $result[] = [
                'sectionid' => (int) $sectionrecord->id,
            ];
        }

        return $result;
    }

    public static function create_sections_returns()
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'sectionid' => new \external_value(PARAM_INT),
            ])
        );
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
            'fields' => new \external_value(PARAM_RAW, 'Optional JSON object with extra module fields', VALUE_DEFAULT, '{}'),
        ]);
    }

    public static function create_module($courseid, $sectionid, $modulename, $name, $intro = '', $introformat = FORMAT_HTML, $visible = 1, $fields = '{}')
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

        $createdmodule = create_module($moduleinfo);
        rebuild_course_cache($course->id);

        return [
            'coursemoduleid' => (int) $createdmodule->coursemodule,
            'instanceid' => (int) $createdmodule->id,
            'sectionid' => (int) $sectionrecord->id,
            'sectionnum' => (int) $sectionrecord->section,
            'modulename' => $params['modulename'],
        ];
    }

    public static function create_module_returns()
    {
        return new \external_single_structure([
            'coursemoduleid' => new \external_value(PARAM_INT),
            'instanceid' => new \external_value(PARAM_INT),
            'sectionid' => new \external_value(PARAM_INT),
            'sectionnum' => new \external_value(PARAM_INT),
            'modulename' => new \external_value(PARAM_TEXT),
        ]);
    }

    public static function update_module_parameters()
    {
        return new \external_function_parameters([
            'coursemoduleid' => new \external_value(PARAM_INT, 'Course module ID'),
            'visible' => new \external_value(PARAM_INT, 'Whether the module is visible', VALUE_OPTIONAL),
            'visibleoncoursepage' => new \external_value(PARAM_INT, 'Whether the module is shown on the course page', VALUE_OPTIONAL),
            'availability' => new \external_value(PARAM_RAW, 'Availability JSON for the module', VALUE_OPTIONAL),
        ]);
    }

    public static function update_module($coursemoduleid, $visible = null, $visibleoncoursepage = null, $availability = null)
    {
        global $CFG, $DB;

        $incoming = ['coursemoduleid' => $coursemoduleid];
        if ($visible !== null) {
            $incoming['visible'] = $visible;
        }
        if ($visibleoncoursepage !== null) {
            $incoming['visibleoncoursepage'] = $visibleoncoursepage;
        }
        if ($availability !== null) {
            $incoming['availability'] = $availability;
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

        if (array_key_exists('visible', $params) || array_key_exists('visibleoncoursepage', $params)) {
            require_capability('moodle/course:activityvisibility', $modcontext);

            $newvisible = array_key_exists('visible', $params) ? (int) $params['visible'] : (int) $cm->visible;
            $newvisibleoncoursepage = array_key_exists('visibleoncoursepage', $params)
                ? (int) $params['visibleoncoursepage']
                : (int) $cm->visibleoncoursepage;

            set_coursemodule_visible($cm->id, $newvisible, $newvisibleoncoursepage);
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
            } else {
                throw new \moodle_exception('availability is not enabled on this site');
            }
        }

        rebuild_course_cache($course->id, true);

        $updatedcm = get_coursemodule_from_id('', $cm->id, 0, false, MUST_EXIST);

        return [
            'coursemoduleid' => (int) $updatedcm->id,
            'visible' => (int) $updatedcm->visible,
            'visibleoncoursepage' => (int) $updatedcm->visibleoncoursepage,
            'availability' => $updatedcm->availability,
        ];
    }

    public static function update_module_returns()
    {
        return new \external_single_structure([
            'coursemoduleid' => new \external_value(PARAM_INT),
            'visible' => new \external_value(PARAM_INT),
            'visibleoncoursepage' => new \external_value(PARAM_INT),
            'availability' => new \external_value(PARAM_RAW, VALUE_OPTIONAL),
        ]);
    }
}
