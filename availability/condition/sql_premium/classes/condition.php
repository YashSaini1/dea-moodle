<?php

namespace availability_sql_premium;

use auth_stripe\model\user_tier;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\subscription\tier_processor;
use core_availability\info;
use core_availability\info_section;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Activity sql_premium condition.
 * This plugin check user premium subscription or if this is free module (by course customfield field)
 *
 * @package availability_sql_premium
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class condition extends \core_availability\condition {

    const PLUGIN_NAME = 'availability_sql_premium';

    const MUST_NOT_HAVE_PREMIUM = 0;

    const MUST_HAVE_PREMIUM = 1;

    /** @var int ID of module that this depends on */
    protected $access_must;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure){
        if (empty($structure->access_must) && $structure->access_must != static::MUST_HAVE_PREMIUM){
            throw new \coding_exception('Missing or invalid acces_must parameter for sql_premium condition');
        }
        $this->access_must = (int)$structure->access_must;
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return stdClass Structure object (ready to be made into JSON format)
     */
    public function save(): stdClass {
        return (object) [
            'type' => 'sql_premium',
            'access_must' => $this->access_must,
        ];
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $expectedsql_premium Expected sql_premium value (sql_premium_xx)
     * @return stdClass Object representing condition
     */
    public static function get_json(int $expectedsql_premium): stdClass {
        return (object) [
            'type' => 'sql_premium',
            'must_access' => (int)$expectedsql_premium,
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
    public function is_available($not, info $info, $grabthelot, $userid): bool{
        if ($info instanceof info_section){
            return true;
        }

        $result = $this->_user_has_subscription($userid) ||
            $this->_check_is_free_cm($info->get_modinfo(), $info->get_course_module()->id);

        if (!$result && !$not) {
            global $PAGE;
            $cm = $info->get_course_module();
            $PAGE->requires->js_call_amd('availability_sql_premium/premium', 'init', ['cm' => $cm->id,
                'str_locked' => (user_tier_output::is_wait_onboarding($userid) ? get_string('onboarding','availability_sql_premium') : get_string('locked','availability_sql_premium'))]);
        }
        if ($not){
            return !$result;
        }
        return $result;
    }

    /**
     * Check is current mod in free position
     *
     * @param \course_modinfo $course_modinfo
     * @param int $cmid
     */
    protected function _check_is_free_cm($course_modinfo, $cmid){
        $free_count = $this->_get_course_free_number($course_modinfo->courseid);
        if (count($course_modinfo->cms) <= $free_count){
            return true;
        }
        $free_modules = $this->_get_free_cms($course_modinfo, $free_count);
        return !empty($free_modules[$cmid]);
    }

    /**
     * @param \course_modinfo $course_modinfo
     * @param int $free_count
     *
     * @return void
     */
    protected function _get_free_cms($course_modinfo, $free_count){
        static $free_course_modules = [];

        $courseid = $course_modinfo->courseid;
        if (!isset($free_course_modules[$courseid])){
            $course_modules = [];
            foreach ($course_modinfo->sections as $section){
                $cm_count = 1;
                foreach ($section as $cmid){
                    if ($cm_count > $free_count){
                        break;
                    }
                    $course_modules[$cmid] = true;
                    $cm_count++;
                }
            }
            $free_course_modules[$courseid] = $course_modules;
        }

        return $free_course_modules[$courseid];
    }

    /**
     * Get and save number of course free modules
     *
     * @param int $courseid
     *
     * @return int|mixed
     */
    protected function _get_course_free_number($courseid){
        static $free_count = [];
        if (!isset($free_count[$courseid])){
            $free_count[$courseid] = \local_sql\moodle\course_customfield::get_number_of_free_questions($courseid);
        }
        return $free_count[$courseid];
    }

    /**
     * Get and save user subscription status
     *
     * @param int $userid
     *
     * @return bool true if user has subscription
     */
    protected function _user_has_subscription($userid){
        static $user_subscriptions = [];
        if (!isset($user_subscriptions[$userid])){
            $user = $this->_get_user($userid);
            $user_subscriptions[$userid] = tier_processor::user_has_tier(user_tier::PREMIUM_TIER, $user);
        }
        return $user_subscriptions[$userid];
    }

    /**
     * Get user object
     *
     * @param int $userid
     *
     * @return bool|mixed|object|stdClass
     */
    protected function _get_user($userid){
        static $users = [];
        global $USER;
        if($USER->id == $userid){
            return $USER;
        }

        if(!isset($users[$userid])){
            $users[$userid] = \core_user::get_user($userid);
        }
        return $users[$userid];
    }

    /**
     * Returns a more readable keyword corresponding to a sql_premium state.
     *
     * Used to make lang strings easier to read.
     *
     * @param int $sql_premiumstate sql_premium_xx constant
     * @return string Readable keyword
     */
    protected static function get_lang_string_keyword(int $sql_premiumstate): string {
        switch($sql_premiumstate) {
            case static::MUST_HAVE_PREMIUM:
                return 'must_have';
            case static::MUST_NOT_HAVE_PREMIUM:
                return 'must_not_have';
            default:
                throw new \coding_exception('Unexpected sql_premium state: ' . $sql_premiumstate);
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
        if ($info instanceof info_section){
            return 'not available for sections';
        }

        $modname = static::description_cm_name($info->get_course_module()->id);
        if ($this->_check_is_free_cm($info->get_modinfo(), $info->get_course_module()->id)){
            return get_string('requires_free_activity', static::PLUGIN_NAME, $modname);
        }

        $value = ($not) ? static::MUST_NOT_HAVE_PREMIUM : static::MUST_HAVE_PREMIUM;
        $str = 'requires_' . static::get_lang_string_keyword($value);

        return get_string($str, static::PLUGIN_NAME, $modname);
    }

    protected function get_debug_string(){
        return 'cm ' . static::get_lang_string_keyword($this->access_must);
    }
}