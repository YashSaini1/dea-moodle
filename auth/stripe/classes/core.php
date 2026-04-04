<?php

namespace auth_stripe;

class core extends \local_sql\core{

    protected static $_trigger_events = true;

    const PLUGIN_NAME = 'auth_stripe';
    const PLUGIN_PATH = 'auth/stripe';

    const MAIN_PAGE = 'main_page';
    const SPECIAL_PREMIUM_PAGE = 'special_premium_page';
    const COACHING_PAGE = 'coaching_page';
    const SECOND_COACHING_PAGE = 'second_coaching_page';

    const TIER_FREE = 'free';
    const TIER_PREMIUM = 'premium';
    const TIER_ANNUAL_PREMIUM = 'annual_premium';
    const TIER_COACHING = 'coaching';
    const TIER_SPECIAL_PREMIUM = 'special_premium';

    const COACHING_PAGES = [
      self::COACHING_PAGE,
      self::SECOND_COACHING_PAGE,
    ];

    const PREMIUM_PAGES = [
      self::MAIN_PAGE,
      self::SPECIAL_PREMIUM_PAGE,
    ];

    const PERIOD_HOUR = 'hour';
    const PERIOD_DAY = 'day';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';
    const PERIOD_ONE_TIME = 'one_time';

    const SUBSCRIPTION_PERIODS = [
        self::PERIOD_DAY,
        self::PERIOD_MONTH,
        self::PERIOD_YEAR,
    ];

    const PDF_FOLDER = '/auth/stripe/pdf';

    public static function is_coaching_page($page){
        return in_array($page, static::COACHING_PAGES);
    }

    public static function is_premium_page($page){
        return in_array($page, static::PREMIUM_PAGES);
    }

    public static function is_period_price($price_type){
        return in_array($price_type, static::SUBSCRIPTION_PERIODS);
    }

    /**
     * This method needs to disable stripe events.
     * It helps with cancelling and creating trial premium subscription
     *
     * @param bool $newvalue
     */
    public static function set_trigger_events($newvalue){
        static::$_trigger_events = $newvalue;
    }

    /**
     * This method using in events to trigger or not.
     *
     * @return bool
     */
    public static function is_trigger_events(){
        return static::$_trigger_events;
    }

    public static function can_view_created_prices(){
        return static::has_capability('view_custom_prices');
    }

    public static function can_create_price(){
        return static::has_capability('create_custom_price');
    }

    public static function can_view_coupons(){
        return static::has_capability('view_coupons');
    }

    public static function can_edit_coupon(){
        return static::has_capability('edit_coupon');
    }

    /**
     * Track and log payment action time
     *
     * @param string $name
     * @param bool $is_end
     */
    public static function track_time($name, $is_end = false){
        static $time = [];
        if ($is_end){
            $result = (hrtime(1) - $time[$name]) / 10 ** 9;
            static::info($name.' elapsed time '.$result);
            unset($time[$name]);
            return;
        }
        $time[$name] = hrtime(1);
        static::info($name.' started');
    }

    public static function log_message($message) {
        static::info($message);
    }

    public static function is_coupon_allowed($page_type){
        return static::is_premium_page($page_type);
    }
}