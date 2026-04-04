<?php

namespace theme_sql;

use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_processor;

class sql_access_to_premium {

    /**
     * @param int $question_position
     * @param int $courseid
     *
     * @return bool
     */
    static function has_access($question_position, $courseid){
        if (tier_processor::user_has_tier(user_tier::PREMIUM_TIER)){
            return true;
        }

        $freecount = \local_sql\moodle\course_customfield::get_number_of_free_questions($courseid);
        return $question_position <= $freecount;
    }
}