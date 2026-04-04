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
 * Activity completion condition.
 *
 * @package availability_coaching_completion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_coaching_completion;

use cache;
use core_availability\info;
use core_availability\info_module;
use core_availability\info_section;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Activity completion condition.
 *
 * @package availability_coaching_completion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int previous module cm value used to calculate relative completions */
    public const OPTION_PREVIOUS = -1;

    /** @var int previous module cm value used to calculate relative completions */
    public const OPTION_PREVIOUS_SECTION = -2;

    const DEFAULT_SECTION = 0;

    /** @var int ID of module that this depends on */
    protected $cmid;

    /** @var int percent of previous section completed */
    protected $percent;

    /** @var array IDs of the current module and section */
    protected $selfids;

    /** @var int Expected completion type (one of the COMPLETE_xx constants) */
    protected $expectedcompletion;

    /** @var array Array of previous cmids used to calculate relative completions */
    protected $modfastprevious = [];

    /** @var array Array of cmids previous to each course section */
    protected $sectionfastprevious = [];

    /** @var array<array{previous:numeric,sectionnum:numeric,count_completed:numeric,count_modules:numeric}> of section completion. */
    protected $completed_sectionfastprevious = [];

    /** @var array Array of modules used in these conditions for course */
    protected static $modsusedincondition = [];

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get cmid.
        if (isset($structure->cm) && is_number($structure->cm)) {
            $this->cmid = (int)$structure->cm;
        } else {
            throw new \coding_exception('Missing or invalid ->cm for completion condition');
        }

        // Completion percent.
        if (isset($structure->percent)) {
            $this->percent = (int)$structure->percent;
        } else {
            $this->percent = 0;
        }
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return stdClass Structure object (ready to be made into JSON format)
     */
    public function save(): stdClass{
        return (object)[
            'type'    => 'coaching_completion',
            'cm'      => $this->cmid,
            'percent' => $this->percent,
        ];
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $cmid    Course-module id of other activity
     * @param int $percent Expected completion value (COMPLETION_xx)
     *
     * @return stdClass Object representing condition
     */
    public static function get_json(int $cmid, $percent): stdClass{
        return (object)[
            'type'    => 'coaching_completion',
            'cm'      => (int)$cmid,
            'percent' => (int)$percent,
        ];
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @see \core_availability\tree_node\update_after_restore
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, info $info, $grabthelot, $userid): bool {
        list($selfcmid, $selfsectionid) = $this->get_selfids($info);
        if ($this->cmid == static::OPTION_PREVIOUS_SECTION){
            $allow = $this->_check_previous_sections($info, $grabthelot, $userid);
        } else {
            $cmid = $this->get_cmid($info->get_course(), $selfcmid, $selfsectionid);
            $allow = $this->_get_allowed($cmid, $info, $grabthelot, $userid);
        }

        if ($not){
            $allow = !$allow;
        }
        return $allow;
    }

    protected function _check_previous_sections(info $info, $grabthelot, $userid){
        $modinfo = $info->get_modinfo();
        $cm = $info->get_course_module();

        $completion = new \completion_info($modinfo->get_course());
        $completiondata = $completion->get_data($cm, $grabthelot, $userid);
        if ($completiondata->completionstate == COMPLETION_COMPLETE || $completiondata->completionstate == COMPLETION_COMPLETE_PASS){
            return true;
        }

        $this->_load_previous_sections($modinfo, $grabthelot, $userid);
        $prev_section_num = $this->completed_sectionfastprevious[$cm->sectionnum]['previous'];

        // First section. Allow this
        if (is_null($prev_section_num)){
            return true;
        }

        $prev_section = $this->completed_sectionfastprevious[$prev_section_num];
        $percent_completed = round(100 * $prev_section['count_completed'] / $prev_section['count_modules']);
        return $percent_completed >= $this->percent;
    }

    protected function _load_previous_sections(\course_modinfo $modinfo, $grabthelot, $userid){
        $course = $modinfo->get_course();
        // If already loaded we don't need to do anything.
        if (empty($this->completed_sectionfastprevious)) {
            $previouscache = cache::make('availability_coaching_completion', 'previous_cache');
            $this->completed_sectionfastprevious = $previouscache->get("prev_sec_{$course->id}");
        }

        if (!empty($this->completed_sectionfastprevious)) {
            return;
        }

        $this->completed_sectionfastprevious = [];

        $sections = $modinfo->get_section_info_all();
        // Save dependence map in these arrays
        $section_completion_map = $section_parents = [];
        $prev_sectionnum = 0;
        foreach ($sections as $section){
            $sectionnum = $section->section;
            $parent = $section->parent  ?? 0;
            if (empty($section_parents[$parent])){
                $section_completion_map[$sectionnum] = $section_completion_map[$parent] ?? static::DEFAULT_SECTION;
                $section_parents[$sectionnum] = [];
                $section_parents[$parent] = [$sectionnum];
                $prev_sectionnum = $sectionnum;
                continue;
            }
            $section_completion_map[$sectionnum] = $prev_sectionnum;
            $section_parents[$sectionnum] = [];
            $section_parents[$parent][] = $sectionnum;
            $prev_sectionnum = $sectionnum;
        }

        $completion = new \completion_info($modinfo->get_course());
        $section_completions= [];
        foreach ($section_completion_map as $section => $depended){
            $result = $this->_get_section_completion($modinfo, $section, $completion, $grabthelot, $userid);
            $result['previous'] = $depended;
            $section_completions[$section] = $result;
        }

        $this->completed_sectionfastprevious = $section_completions;
        $previouscache->set("prev_sec_{$course->id}", $this->completed_sectionfastprevious);
    }

    protected function _get_section_completion(\course_modinfo $modinfo, $sectionnum, $completion, $grabthelot, $userid){
        if (empty($modinfo->sections[$sectionnum])){
            // set count to one to avoid division by zero
            return [
                'sectionnum'      => $sectionnum,
                'count_completed' => 1,
                'count_modules'   => 1,
            ];
        }

        $count_completed = $count_modules = 0;
        foreach ($modinfo->sections[$sectionnum] as $cmid){
            $cm = $modinfo->get_cm($cmid);
            if ($cm->deletioninprogress){
                continue;
            }

            // Load previous to all cms with completion.
            if ($cm->completion == COMPLETION_TRACKING_NONE){
                continue;
            }
            // The completion system caches its own data so no caching needed here.
            $completiondata = $completion->get_data((object)['id' => $cm->id], $grabthelot, $userid);
            if ($completiondata->completionstate == COMPLETION_COMPLETE || $completiondata->completionstate == COMPLETION_COMPLETE_PASS){
                $count_completed++;
            }
            $count_modules++;
        }
        return [
            'sectionnum'      => $sectionnum,
            'count_completed' => $count_completed,
            'count_modules'   => $count_modules,
        ];
    }

    protected function _get_allowed($cmid, info $info, $grabthelot, $userid){
        $modinfo = $info->get_modinfo();
        $completion = new \completion_info($modinfo->get_course());
        if (!array_key_exists($cmid, $modinfo->cms) || $modinfo->cms[$cmid]->deletioninprogress){
            // If the cmid cannot be found, always return false regardless
            // of the condition or $not state. (Will be displayed in the
            // information message.)
            $allow = false;
        } else {
            // The completion system caches its own data so no caching needed here.
            $completiondata = $completion->get_data((object)['id' => $cmid], $grabthelot, $userid);

            $allow = true;
            // Complete also allows the pass, fail states.
            switch ($completiondata->completionstate){
                case COMPLETION_COMPLETE:
                case COMPLETION_COMPLETE_FAIL:
                case COMPLETION_COMPLETE_PASS:
                    break;
                default:
                    $allow = false;
            }
        }
        return $allow;
    }

    /**
     * Return current item IDs (cmid and sectionid).
     *
     * @param info $info
     * @return int[] with [0] => cmid/null, [1] => sectionid/null
     */
    public function get_selfids(info $info): array {
        if (isset($this->selfids)) {
            return $this->selfids;
        }
        if ($info instanceof info_module) {
            $cminfo = $info->get_course_module();
            if (!empty($cminfo->id)) {
                $this->selfids = [$cminfo->id, null];
                return $this->selfids;
            }
        }
        if ($info instanceof info_section) {
            $section = $info->get_section();
            if (!empty($section->id)) {
                $this->selfids = [null, $section->id];
                return $this->selfids;
            }

        }
        return [null, null];
    }

    /**
     * Get the cmid referenced in the access restriction.
     *
     * @param stdClass $course course object
     * @param int|null $selfcmid current course-module ID or null
     * @param int|null $selfsectionid current course-section ID or null
     * @return int|null cmid or null if no referenced cm is found
     */
    public function get_cmid(stdClass $course, ?int $selfcmid, ?int $selfsectionid): ?int {
        if ($this->cmid > 0) {
            return $this->cmid;
        }
        // If it's a relative completion, load fast browsing.
        if ($this->cmid == static::OPTION_PREVIOUS) {
            $prevcmid = $this->get_previous_cmid($course, $selfcmid, $selfsectionid);
            if ($prevcmid) {
                return $prevcmid;
            }
        }
        return null;
    }

    /**
     * Return the previous CM ID of an specific course-module or course-section.
     *
     * @param stdClass $course course object
     * @param int|null $selfcmid course-module ID or null
     * @param int|null $selfsectionid course-section ID or null
     * @return int|null
     */
    private function get_previous_cmid(stdClass $course, ?int $selfcmid, ?int $selfsectionid): ?int {
        $this->load_course_structure($course);
        if (isset($this->modfastprevious[$selfcmid])) {
            return $this->modfastprevious[$selfcmid];
        }
        if (isset($this->sectionfastprevious[$selfsectionid])) {
            return $this->sectionfastprevious[$selfsectionid];
        }
        return null;
    }

    /**
     * Loads static information about a course elements previous activities.
     *
     * Populates two variables:
     *   - $this->sectionprevious[] course-module previous to a cmid
     *   - $this->sectionfastprevious[] course-section previous to a cmid
     *
     * @param stdClass $course course object
     */
    private function load_course_structure(stdClass $course): void {
        // If already loaded we don't need to do anything.
        if (empty($this->modfastprevious)) {
            $previouscache = cache::make('availability_coaching_completion', 'previous_cache');
            $this->modfastprevious = $previouscache->get("mod_{$course->id}");
            $this->sectionfastprevious = $previouscache->get("sec_{$course->id}");
        }

        if (!empty($this->modfastprevious)) {
            return;
        }

        if (empty($this->modfastprevious)) {
            $this->modfastprevious = [];
            $sectionprevious = [];

            $modinfo = get_fast_modinfo($course);
            $lastcmid = 0;
            foreach ($modinfo->cms as $othercm) {
                if ($othercm->deletioninprogress) {
                    continue;
                }
                // Save first cm of every section.
                if (!isset($sectionprevious[$othercm->section])) {
                    $sectionprevious[$othercm->section] = $lastcmid;
                }
                // Load previous to all cms with completion.
                if ($othercm->completion == COMPLETION_TRACKING_NONE) {
                    continue;
                }
                if ($lastcmid) {
                    $this->modfastprevious[$othercm->id] = $lastcmid;
                }
                $lastcmid = $othercm->id;
            }
            // Fill empty sections index.
            $isections = array_reverse($modinfo->get_section_info_all());
            foreach ($isections as $section) {
                if (isset($sectionprevious[$section->id])) {
                    $lastcmid = $sectionprevious[$section->id];
                } else {
                    $sectionprevious[$section->id] = $lastcmid;
                }
            }
            $this->sectionfastprevious = $sectionprevious;
            $previouscache->set("mod_{$course->id}", $this->modfastprevious);
            $previouscache->set("sec_{$course->id}", $this->sectionfastprevious);
        }
    }

    /**
     * Returns a more readable keyword corresponding to a completion state.
     *
     * Used to make lang strings easier to read.
     *
     * @param int $completionstate COMPLETION_xx constant
     * @return string Readable keyword
     */
    protected static function get_lang_string_keyword(int $completionstate): string {
        switch($completionstate) {
            case COMPLETION_INCOMPLETE:
                return 'incomplete';
            case COMPLETION_COMPLETE:
                return 'complete';
            case COMPLETION_COMPLETE_PASS:
                return 'complete_pass';
            case COMPLETION_COMPLETE_FAIL:
                return 'complete_fail';
            default:
                throw new \coding_exception('Unexpected completion state: ' . $completionstate);
        }
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies).
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, info $info): string {
        global $USER;
        $str = 'requires_';
        $course = $info->get_course();
        list($selfcmid, $selfsectionid) = $this->get_selfids($info);
        $modname = '';
        // On ajax duplicate get_fast_modinfo is called before $PAGE->set_context
        // so we cannot use $PAGE->user_is_editing().
        $coursecontext = \context_course::instance($course->id);
        $editing = !empty($USER->editing) && has_capability('moodle/course:manageactivities', $coursecontext);
        if ($this->cmid == static::OPTION_PREVIOUS && $editing) {
            // Previous activity name could be inconsistent when editing due to partial page loadings.
            $str .= 'previous_';
        } elseif ($this->cmid == static::OPTION_PREVIOUS_SECTION){
            $str .= 'previous_section_';
        } else {
            // Get name for module.
            $cmid = $this->get_cmid($course, $selfcmid, $selfsectionid);
            $modinfo = $info->get_modinfo();
            if (!array_key_exists($cmid, $modinfo->cms) || $modinfo->cms[$cmid]->deletioninprogress) {
                $modname = get_string('missing', 'availability_coaching_completion');
            } else {
                $modname = static::description_cm_name($modinfo->cms[$cmid]->id);
            }
        }

        // Work out which lang string to use depending on required completion status.
        if ($not) {
            $str .= static::get_lang_string_keyword(COMPLETION_INCOMPLETE);
        } else {
            $str .= static::get_lang_string_keyword(COMPLETION_COMPLETE);
        }

        return get_string($str, 'availability_coaching_completion', $modname);
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string(): string {
        switch ($this->expectedcompletion) {
            case COMPLETION_COMPLETE :
                $type = 'COMPLETE';
                break;
            case COMPLETION_INCOMPLETE :
                $type = 'INCOMPLETE';
                break;
            case COMPLETION_COMPLETE_PASS:
                $type = 'COMPLETE_PASS';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $type = 'COMPLETE_FAIL';
                break;
            default:
                throw new \coding_exception('Unexpected expected completion');
        }
        $cm = $this->cmid;
        if ($this->cmid == static::OPTION_PREVIOUS) {
            $cm = 'opprevious';
        }
        return 'cm' . $cm . ' ' . $type;
    }

    /**
     * Updates this node after restore, returning true if anything changed.
     *
     * @see \core_availability\tree_node\update_after_restore
     *
     * @param string $restoreid Restore ID
     * @param int $courseid ID of target course
     * @param \base_logger $logger Logger for any warnings
     * @param string $name Name of this item (for use in warning messages)
     * @return bool True if there was any change
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        global $DB;
        $res = false;
        // If we depend on the previous activity, no translation is needed.
        if ($this->cmid == static::OPTION_PREVIOUS || $this->cmid == static::OPTION_PREVIOUS_SECTION) {
            return $res;
        }
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'course_module', $this->cmid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('course_modules',
                    ['id' => $this->cmid, 'course' => $courseid])) {
                return $res;
            }
            // Otherwise it's a warning.
            $this->cmid = 0;
            $logger->process('Restored item (' . $name .
                    ') has availability condition on module that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->cmid = (int)$rec->newitemid;
        }
        return true;
    }

    /**
     * Used in course/lib.php because we need to disable the completion JS if
     * a completion value affects a conditional activity.
     *
     * @param \stdClass $course Moodle course object
     * @param int $cmid Course-module id
     * @return bool True if this is used in a condition, false otherwise
     */
    public static function completion_value_used($course, $cmid): bool {
        // Have we already worked out a list of required completion values
        // for this course? If so just use that.
        if (!array_key_exists($course->id, static::$modsusedincondition)) {
            // We don't have data for this course, build it.
            $modinfo = get_fast_modinfo($course);
            static::$modsusedincondition[$course->id] = [];

            // Activities.
            foreach ($modinfo->cms as $othercm) {
                if (is_null($othercm->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_module($othercm);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children('availability_coaching_completion\condition') as $cond) {
                    $condcmid = $cond->get_cmid($course, $othercm->id, null);
                    if (!empty($condcmid)) {
                        static::$modsusedincondition[$course->id][$condcmid] = true;
                    }
                }
            }

            // Sections.
            foreach ($modinfo->get_section_info_all() as $section) {
                if (is_null($section->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_section($section);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children('availability_coaching_completion\condition') as $cond) {
                    $condcmid = $cond->get_cmid($course, null, $section->id);
                    if (!empty($condcmid)) {
                        static::$modsusedincondition[$course->id][$condcmid] = true;
                    }
                }
            }
        }
        return array_key_exists($cmid, static::$modsusedincondition[$course->id]);
    }

    /**
     * Wipes the static cache of modules used in a condition (for unit testing).
     */
    public static function wipe_static_cache() {
        static::$modsusedincondition = [];
    }

    public function update_dependency_id($table, $oldid, $newid) {
        if ($table === 'course_modules' && (int)$this->cmid === (int)$oldid) {
            $this->cmid = $newid;
            return true;
        } else {
            return false;
        }
    }
}
