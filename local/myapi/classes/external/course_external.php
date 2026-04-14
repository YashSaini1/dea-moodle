<?php

namespace local_myapi\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class course_external extends \external_api
{

    public static function get_course_full_data_parameters()
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function get_course_full_data($courseid)
    {
        global $DB;

        $params = self::validate_parameters(
            self::get_course_full_data_parameters(),
            ['courseid' => $courseid]
        );

        $modinfo = get_fast_modinfo($courseid);

        $data = [];

        foreach ($modinfo->get_section_info_all() as $section) {

            $sectiondata = [
                'id' => $section->id,
                'name' => $section->name ?: "Section " . $section->section,
                'section' => $section->section,
                'modules' => []
            ];

            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];

                    $sectiondata['modules'][] = [
                        'id' => $cm->id,
                        'name' => $cm->name,
                        'modname' => $cm->modname,
                        'url' => $cm->url ? $cm->url->out(false) : null,
                        'visible' => $cm->visible
                    ];
                }
            }

            $data[] = $sectiondata;
        }

        return $data;
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
                        'visible' => new \external_value(PARAM_INT)
                    ])
                )
            ])
        );
    }
}
