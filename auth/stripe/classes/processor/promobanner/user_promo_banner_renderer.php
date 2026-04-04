<?php

namespace auth_stripe\processor\promobanner;

use auth_stripe\core;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\user_promo_banner;
use auth_stripe\model\user_tier;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\subscription\tier_processor;

class user_promo_banner_renderer {

    /**
     * @var bool variable which is setted to hide premium button in the header
     */
    protected static $_start_render = false;
    /**
     * @var bool|mixed
     */
    public static $black_friday_coupon = 'BLKFRI60';

    /** @var user_promo_banner */
    protected $_user_promo_banner;

    protected const DISABLED_PAGELAYOUTS = [
        'questioneditor' => 1,
        'notice' => 1,
        'login' => 1,
        'embed' => 1,
    ];

    protected const DISABLED_PAGETYPES = [
        'terms' => 1,
        'privacy_policy' => 1,
    ];

    public static function is_should_rendered(){
        static $should_rendered = null;
        if (is_null($should_rendered)){
            $should_rendered = static::_calculate_should_rendered();
        }
        return $should_rendered && !static::$_start_render;
    }

    protected static function _calculate_should_rendered(){
        global $USER, $PAGE;

        $is_black_friday = self::black_friday();

        if (user_tier_output::is_wait_onboarding($USER->id)) {
            return false;
        }

        if (!$is_black_friday && empty($USER->{user_promo_banner::USER_FIELD})){
            return false;
        }

        if (tier_processor::user_has_tier(user_tier::COACHING_TIER)){
            return false;
        }

        $pagelayout = $PAGE->pagelayout;
        if (!empty(static::DISABLED_PAGELAYOUTS[$pagelayout])){
            return false;
        }

        $pagetype = $PAGE->pagetype;
        if (!empty(static::DISABLED_PAGETYPES[$pagetype]) || preg_match('/^auth-stripe-payment/', $pagetype)){
            return false;
        }

        $course = $PAGE->course;
        if ($course && $course->id != SITEID && \local_sql\coaching::is_coaching_course($course->id)){
            return false;
        }

        $promobanner_config = promobanner_config::get_instance();
        if (!$promobanner_config->enabled){
            return false;
        }

        if (!$is_black_friday && $USER->{user_promo_banner::USER_FIELD}->timedue <= time()){
            return false;
        }

        return true;
    }

    /**
     * @param \stdClass|user_promo_banner $user_promo_banner
     */
    public function __construct($user_promo_banner = null){
        if (!empty($user_promo_banner)){
            $this->set_user_promo_banner($user_promo_banner);
        }
    }

    public function set_user_promo_banner($user_promo_banner): void{

        $is_black_friday = self::black_friday();

        if ($is_black_friday) {
            $this->_user_promo_banner = $user_promo_banner;
            return;
        }

        if (empty($user_promo_banner)){
            core::error('Invalid user promo banner!', [$user_promo_banner]);
            throw new \Exception('Invalid user promo banner!');
        }

        if (!$user_promo_banner instanceof user_promo_banner){
            $user_promo_banner_model = user_promo_banner::process_from_record($user_promo_banner);
            if (!$user_promo_banner_model){
                core::error('Invalid user promo banner!', [$user_promo_banner]);
                throw new \Exception('Invalid user promo banner!');
            }
            $user_promo_banner = $user_promo_banner_model;
        }
        $this->_user_promo_banner = $user_promo_banner;
    }

    public function render(){

        $is_black_friday = self::black_friday();

        if (empty($this->_user_promo_banner)){
            return '';
        }
        if ($is_black_friday || $this->_user_promo_banner->type_is(user_promo_banner::TYPE_NEW_USER)){
            static::$_start_render = true;
        }
        $banner_config = promobanner_config::get_instance();

        $tier_output = new user_tier_output();
        $upgrade_url = $tier_output->get_upgrade_url([
            'apply_coupon' => $banner_config->couponname
        ]);
        if (!$upgrade_url){
            return '';
        }
        $upgrade_url = html_entity_decode((string)$upgrade_url);

        return core::render_from_template('promobanner/banner', [
            'user_banner'   => $this->_user_promo_banner,
            'banner_config' => $banner_config,
            'upgrade_url'   => $upgrade_url,
            'black_friday'  => $is_black_friday,
        ]);
    }

    public static function black_friday() {
        $promobanner_config = promobanner_config::get_instance();
        return $promobanner_config->blackfriday ?? false;
    }
}