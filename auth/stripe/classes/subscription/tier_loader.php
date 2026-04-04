<?php

namespace auth_stripe\subscription;

use auth_stripe\model\user_tier;


//TODO: move this to the loaders folder
/**
 * Base class to process moodle subscriptions
 */
class tier_loader {

    /**
     * @param $user_or_id
     * @param $force_load
     *
     * @return user_tier[]
     */
    public static function get_all_by_user($user_or_id = null, $force_load = false, $onboarding = false){
        static $user_tiers = [];
        $userid = \auth_stripe\core::get_userid($user_or_id);
        if (!array_key_exists($userid, $user_tiers) || $force_load){
            $user_tiers[$userid] = user_tier::get_all(['userid' => $userid], 'id', '', $onboarding);
        }
        return $user_tiers[$userid];
    }
}