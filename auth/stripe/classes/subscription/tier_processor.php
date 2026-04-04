<?php

namespace auth_stripe\subscription;

use auth_stripe\core;
use auth_stripe\model\product;
use auth_stripe\model\user_tier;
use local_sql\moodle\role_manager;

/**
 * Base class to process moodle subscriptions
 */
class tier_processor {

    /**
     * Create and save empty user tier
     *
     * @param int $userid
     *
     * @return user_tier
     */
    public static function empty_tier($userid){
        if ($tier = user_tier::get(['userid' => $userid, 'tier' => user_tier::FREE_TIER])){
            return $tier;
        }

        $tier = new user_tier([
            'userid'               => $userid,
            'tier'                 => user_tier::FREE_TIER,
            'can_cancel'           => 0,
            'current_period_end'   => 0,
            'current_period_start' => time(),
        ]);
        $tier->save();
        return $tier;
    }

    public static function init_user_tiers($user, $force_load = false, $onboarding = false){
        $user->tier = tier_loader::get_all_by_user($user, $force_load, $onboarding);
        return $user;
    }

    protected static function _check_user($user = null){
        if (empty($user)){
            global $USER;
            $user = $USER;
        }
        // check empty here, because sumetimes $USER->tier contains an empty array even if user has any tier
        if (empty($user->tier)){
            $user = static::init_user_tiers($user);
        }
        return $user;
    }

    /**
     * @param int            $tier user_tier::$tier
     * @param \stdClass|null $user
     * @param bool           $return_tier
     *
     * @return bool|user_tier
     */
    public static function user_has_tier($tier, $user = null, $return_tier = false){
        if (role_manager::is_admin($user)){
            return true;
        }

        $user = static::_check_user($user);

        switch ($tier){
            case user_tier::FREE_TIER:
                $function = 'is_free';
                break;
            case user_tier::PREMIUM_TIER:
                $function = 'is_premium';
                break;
            case user_tier::COACHING_TIER:
                $function = 'is_coaching';
                break;
            case user_tier::SPECIAL_PREMIUM_TIER:
                $function = 'is_special_premium';
                break;
            default:
                core::error('Trying to check undefined user tier \''.$tier.'\'');
                return false;
        }

        foreach ($user->tier as $t){
            if ($t->$function()){
                return $return_tier ? $t : true;
            }
        }
        return false;
    }

    public static function check_page($page, $user = null): bool{
        $user = static::_check_user($user);
        if (core::is_coaching_page($page)){
            $products = product::get_all_by_page(core::COACHING_PAGES);
        } elseif (core::is_premium_page($page)) {
            $products = product::get_all_by_page(core::PREMIUM_PAGES);
        } else {
            core::error('Trying to check undefined page \''.$page.'\'');
            return false;
        }

        foreach ($products as $product){
            if (array_key_exists($product->tier, $user->tier)){
                return true;
            }
        }
        return false;
    }
}