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
 * Blocks external API
 *
 * @package    block_sql_myoverview
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

use auth_stripe\output\stripe\user_tier_output;
use block_sql_myoverview\customprogress;
use core_course\external\course_summary_exporter;
use local_sql\coaching;
use local_sql\moodle\role_manager;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/availability/condition/extended/lib.php");
/**
 * Blocks external functions
 *
 * @package    block_sql_myoverview
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class block_sql_myoverview_external extends external_api {

    /**
     * Course ID with priority
     */
    const PRIORITY_COURSE_ID = 26;

    /**
     * Returns a block structure.
     *
     * @return external_single_structure a block single structure.
     * @since  Moodle 3.6
     */
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_by_timeline_classification_parameters() {
        return new external_function_parameters(
            array(
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null),
                'customfieldname' => new external_value(PARAM_ALPHANUMEXT, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'customfieldvalue' => new external_value(PARAM_RAW, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'searchvalue' => new external_value(PARAM_RAW, 'The value a user wishes to search against',
                    VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Get courses matching the given timeline classification.
     *
     * NOTE: The offset applies to the unfiltered full set of courses before the classification
     * filtering is done.
     * E.g.
     * If the user is enrolled in 5 courses:
     * c1, c2, c3, c4, and c5
     * And c4 and c5 are 'future' courses
     *
     * If a request comes in for future courses with an offset of 1 it will mean that
     * c1 is skipped (because the offset applies *before* the classification filtering)
     * and c4 and c5 will be return.
     *
     * @param  string $classification past, inprogress, or future
     * @param  int $limit Result set limit
     * @param  int $offset Offset the full course set before timeline classification is applied
     * @param  string $sort SQL sort string for results
     * @param  string $customfieldname
     * @param  string $customfieldvalue
     * @param  string $searchvalue
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     */
    public static function get_enrolled_courses_by_timeline_classification(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null,
        string $customfieldname = null,
        string $customfieldvalue = null,
        string $searchvalue = null
    ) {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(self::get_enrolled_courses_by_timeline_classification_parameters(),
            array(
                'classification' => $classification,
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
                'customfieldvalue' => $customfieldvalue,
                'searchvalue' => $searchvalue,
            )
        );

        $classification = $params['classification'];
        $limit = $params['limit'];
        $offset = $params['offset'];
        $sort = $params['sort'];
        $customfieldvalue = $params['customfieldvalue'];
        $searchvalue = clean_param($params['searchvalue'], PARAM_TEXT);

        switch($classification) {
            case COURSE_TIMELINE_ALLINCLUDINGHIDDEN:
                break;
            case COURSE_TIMELINE_ALL:
                break;
            case COURSE_TIMELINE_PAST:
                break;
            case COURSE_TIMELINE_INPROGRESS:
                break;
            case COURSE_TIMELINE_FUTURE:
                break;
            case COURSE_FAVOURITES:
                break;
            case COURSE_TIMELINE_HIDDEN:
                break;
            case COURSE_TIMELINE_SEARCH:
                break;
            case COURSE_CUSTOMFIELD:
                break;
            default:
                throw new invalid_parameter_exception('Invalid classification');
        }

        self::validate_context(context_user::instance($USER->id));

        $requiredproperties = course_summary_exporter::define_properties();
        $fields = join(',', array_keys($requiredproperties));
        $hiddencourses = get_hidden_courses_on_timeline();
        $courses = [];
        $coaching_courses = coaching::get_courses();

        if (!role_manager::is_admin($USER->id)) {
            $coaching_courses = coaching::remove_unvisible($coaching_courses);
        }

        $count_coaching_courses = count($coaching_courses);
        $wait_onboarding = user_tier_output::is_wait_onboarding($USER->id);
        $user_has_coaching = coaching::has_coaching();
        $add_coaching = ($offset == 0);
        if ($add_coaching && !$user_has_coaching){
            if ($classification == COURSE_TIMELINE_SEARCH){
                $searchcriteria['search'] = $searchvalue;
                $options = ['idonly' => true];
                $ids = core_course_category::search_courses($searchcriteria, $options);
                $result = [];
                /** @var int $id */
                foreach ($ids as $id){
                    if (array_key_exists($id, $coaching_courses)){
                        $result[] = $coaching_courses[$id];
                    }
                }
                $coaching_courses = $result;
                $count_coaching_courses = count($coaching_courses);
            }
//            $limit -= $count_coaching_courses;
        }

        // If the timeline requires really all courses, get really all courses.
        if ($classification == COURSE_TIMELINE_ALLINCLUDINGHIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields, COURSE_DB_QUERY_LIMIT);

            // Otherwise if the timeline requires the hidden courses then restrict the result to only $hiddencourses.
        } else if ($classification == COURSE_TIMELINE_HIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, $hiddencourses);

            // Otherwise get the requested courses and exclude the hidden courses.
        } else if ($classification == COURSE_TIMELINE_SEARCH) {
            // Prepare the search API options.
            $searchcriteria['search'] = $searchvalue;
            $options = ['idonly' => true];
            $courses = course_get_enrolled_courses_for_logged_in_user_from_search(
                0,
                $offset,
                $sort,
                $fields,
                COURSE_DB_QUERY_LIMIT,
                $searchcriteria,
                $options
            );
        } else {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
        }

        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

        if ($favourites) {
            $favouritecourseids = array_map(
                function($favourite) {
                    return $favourite->itemid;
                }, $favourites);
        }

        if ($classification == COURSE_FAVOURITES) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_favourites(
                $courses,
                $favouritecourseids,
                $limit
            );
        } else if ($classification == COURSE_CUSTOMFIELD) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_customfield(
                $courses,
                $customfieldname,
                $customfieldvalue,
                $limit
            );
        } else {
            list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
                $courses,
                $classification,
                $limit
            );
        }

        $renderer = $PAGE->get_renderer('core');
        $formattedcourses = array_map(function($course) use ($renderer, $favouritecourseids) {
            if ($course == null) {
                return;
            }
            context_helper::preload_from_record($course);
            $context = context_course::instance($course->id);
            $isfavourite = false;
            if (in_array($course->id, $favouritecourseids)) {
                $isfavourite = true;
            }
            $exporter = new customprogress($course, ['context' => $context, 'isfavourite' => $isfavourite]);
            return $exporter->export($renderer);
        }, $filteredcourses);

        $formattedcourses = array_filter($formattedcourses);

        $coaching_link = get_config('block_sql_myoverview', 'disabled_coaching_link');
        if (!empty($coaching_link)) {
            $coaching_link = (new moodle_url($coaching_link))->out(false);
        } else {
            $coaching_link = '#';
        }

        // Filter coaching courses
        if ($count_coaching_courses > 0){
            if ($user_has_coaching){
                $courses_to_add = [];
                foreach ($formattedcourses as $pos => $course){
                    if (array_key_exists($course->id, $coaching_courses)){
                        unset($formattedcourses[$pos]);
                        if ($wait_onboarding) {
                            $course->viewurl = $coaching_link;
                            $course->disabled_coaching = true;
                            $course->hasprogress = false;
                        } else {
                            $course->coachingcourse = true;
                        }
                        $course->waitonboarding = $wait_onboarding;
                        $courses_to_add[] = $course;
                    }
                }
                array_unshift($formattedcourses, ...$courses_to_add);
                // if user not has coaching and he opens the first page
            } else {
                foreach ($formattedcourses as $pos => $course){
                    if (array_key_exists($course->id, $coaching_courses)){
                        unset($formattedcourses[$pos]);
                    }
                }

                if ($offset == 0){
                    $process_coaching_course = function($course) use ($renderer, $favouritecourseids) {
                        if ($course == null) {
                            return;
                        }
                        context_helper::preload_from_record($course);
                        $context = context_course::instance($course->id);
                        $isfavourite = false;
                        if (in_array($course->id, $favouritecourseids)) {
                            $isfavourite = true;
                        }
                        $exporter = new customprogress($course, ['context' => $context, 'isfavourite' => $isfavourite]);
                        return $exporter->export($renderer);
                    };

                    $courses_to_add = [];
                    foreach ($coaching_courses as $coaching_course) {
                        $course = $process_coaching_course($coaching_course);

                        if (!user_admitted($coaching_course->id)) {
                            $course->viewurl = ($course->shortname == "AI" ? "https://dataengineeracademy.com/courses/generative-ai-large-language-models/" : $coaching_link); // Hardcode I'll rewrite it on the next bug fix
                            $course->disabled_coaching = true;
                            $course->hasprogress = false;
                            $course->waitonboarding = false;
                        } else {
                            $course->coachingcourse = true;
                            $course->hasprogress = true;
                        }

                        $courses_to_add[] = $course;
                    }
                    array_unshift($formattedcourses, ...$courses_to_add);
                }
            }
        }
        $formattedcourses = array_values($formattedcourses);

        // HARDCODE START
        $prioritycourse = null;
        $othercourses = [];

        foreach ($formattedcourses as $course) {
            if ($course->id == self::PRIORITY_COURSE_ID) {
                $prioritycourse = $course;
            } else {
                $othercourses[] = $course;
            }
        }

        if ($prioritycourse !== null) {
            $formattedcourses = array_merge([$prioritycourse], $othercourses);
        } else {
            $formattedcourses = $othercourses;
        }
        // HARDCODE END

        return [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount,
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_enrolled_courses_by_timeline_classification_returns() {
        return new external_single_structure(
            array(
                'courses' => new external_multiple_structure(customprogress::get_read_structure(), 'Course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request')
            )
        );
    }
}
