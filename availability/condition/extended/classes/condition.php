<?php

namespace availability_extended;

use auth_stripe\model\user_tier;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\subscription\tier_processor;
use core_availability\info;
use core_availability\info_section;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Activity extended condition.
 * This plugin check user premium subscription or if this is free module (by course customfield field)
 *
 * @package availability_extended
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class condition extends \core_availability\condition {

    const PLUGIN_NAME = 'availability_extended';
    protected $access_must;

    public function __construct($structure){
        $this->access_must = (int)$structure->access_must;
    }

    public function save(): stdClass {
        return (object) [
            'type' => 'extended',
            'access_must' => $this->access_must,
        ];
    }

    public static function get_json(int $expectedextended): stdClass {
        return (object) [
            'type' => 'extended',
            'must_access' => (int)$expectedextended,
        ];
    }

    public function is_available($not, info $info, $grabthelot, $userid): bool {
        global $DB;

        if (!method_exists($info, 'get_section')) {
            return true;
        }

        $section = $info->get_section();
        $sectionid = $section->id;

        $result = $DB->record_exists('sql_extended', array('sectionid' => $sectionid, 'userid' => $userid));

        if ($not){
            return !$result;
        }
        return $result;
    }

    public function get_description($full, $not, info $info): string {
        return get_string('requires_free_activity', static::PLUGIN_NAME);
    }

    protected function get_debug_string(){
        return '';
    }
}