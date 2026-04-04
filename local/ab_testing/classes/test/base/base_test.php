<?php

namespace local_ab_testing\test\base;

use local_ab_testing\ab_test_configurator;
use local_ab_testing\core;
use local_ab_testing\util\utm_util;

/**
 * Base class for all tests.
 * It's contains all necessary class_test logic.
 *
 * All configuration logic moved to the {@see test_config} class
 */
abstract class base_test extends test_config {
    use session_manager {
        // resolve function name conflict
        session_manager::init as session_manager_init;
    }

    const JS_PRICE_FIELD = '${price}';
    const JS_CAMPAIGN_FIELD = '${campaign}';

    const TEST_NAME = '';

//    protected static bool $_enabled = false;
    protected static array $_enabled_data = [];

    public static function init(){
        static::_load_config();
        if (static::is_enabled()){
            static::session_manager_init();
        }
    }

    public static function _get_test_name(){
        return static::TEST_NAME;
    }

    protected static function _load_config(){
        if (!core::is_ab_enabled()){
            return;
        }

        $all_settings = ab_test_configurator::get_settings();
        if (empty($all_settings[static::TEST_NAME]) || empty($all_settings[static::TEST_NAME][static::_FIELD_ENABLED])){
            static::$_enabled_data[static::TEST_NAME] = false;
            return;
        }

        static::$_configuration = $all_settings[static::TEST_NAME];
        static::$_enabled_data[static::TEST_NAME] = true;
    }

    public static function is_enabled(): bool{
        return static::$_enabled_data[static::TEST_NAME];
    }

    protected static function _load_utm_data(){
        if (!static::is_enabled()){
            return false;
        }

        $utm_params = static::get_all_metrics();
        if (empty($utm_params)){
            return false;
        }

        $utm_metrics_data = [];
        foreach ($utm_params as $utm){
            $umt_data = optional_param($utm, false, PARAM_TEXT);
            if ($umt_data === false){
                return false;
            }
            $utm_metrics_data[$utm] = $umt_data;
        }

        $campaign = $utm_metrics_data[static::get_campaign_parameter()];
        return in_array($campaign, static::get_available_campaigns()) ? $utm_metrics_data : false;
    }

    protected static function _get_all_active_tests(){
        static $active_tests = null;
        if (is_null($active_tests)){
            $active_tests = [];

            $settings = ab_test_configurator::get_settings();
            foreach ($settings as $test_name => $test_config){
                if (!$test_config[self::_FIELD_ENABLED]){
                    continue;
                }

                $full_test_classname = '\\local_ab_testing\\test\\'.$test_name.'_test';
                if (class_exists($full_test_classname)){
                    $active_tests[] = $full_test_classname;
                }
            }
        }
        return $active_tests;
    }

    public static function trigger_hook($hook, ...$args){
        if (!core::is_ab_enabled() || \local_sql\moodle\role_manager::is_admin()){
            return;
        }

        $method_name = 'hook_'.$hook;
        if (!method_exists(static::class, $method_name)){
            return;
        }

        $active_tests = static::_get_all_active_tests();
        foreach ($active_tests as $test){
            $test::$method_name($args);
        }
    }

    public static function hook_page_open(){ }

    /**
     * @param $hook_data array 0 - userid, 1 -
     *
     * @return void
     */
    public static function hook_user_created($hook_data){
        if (empty($hook_data[0])){
            throw new \moodle_exception('Userid must be specified in user created hook!');
        }

        static::update_profile_data($hook_data[0], true);
    }

    /**
     * Validate page. If user not visited this page during A/B testing, add utm metrics
     *
     * @param string $page_url
     *
     * @return void
     * @throws \moodle_exception
     */
    public static function check_page($page_url){
        if (!static::is_enabled() || static::_load_utm_data() !== false){
            return;
        }

        $pages = static::get_all_pages();
        if (!array_key_exists($page_url, $pages)){
            return;
        }

        $user_campaign = static::get_user_campaign();
        if (empty($user_campaign)){
            $user_campaign = static::get_default_campaign();
        }

        $visited_urls = static::_get_param($user_campaign) ?? [];
        if (array_key_exists($page_url, $visited_urls)){
            return;
        }

        if (!static::validate_base_page($user_campaign, $page_url)){
            return;
        }

        $utm_query = static::build_utm_query_url($user_campaign, $page_url);
        $utm_url = new \moodle_url(utm_util::add_utm_to_url($page_url, $utm_query));

        // Save current url parameters (such as coupon)
        global $PAGE;
        $page_url = $PAGE->url;
        if ($page_url){
            $utm_url->params($page_url->params());
        }
        redirect($utm_url);
    }

    /**
     * Validate second utm metric visit
     *
     * @return bool
     */
    public static function validate_redirect(): bool{
        global $ME;

        $utm_data = static::_load_utm_data();
        if ($utm_data === false){
            return true;
        }

        $utm3 = $utm_data[static::get_campaign_parameter()];

        // if campaign not available for current test, check if another test exists
        if (!static::is_available_campaign($utm3)){
            return count(static::_get_all_active_tests()) > 1;
        }

        $script = utm_util::clean_url($ME);
        if (!static::validate_base_page($utm3, $script)){
            return false;
        }

        if ($script == static::get_final_page_url()){
            global $USER;
            core::info('User '.$USER->id.' - '.$USER->email.' completed '.static::TEST_NAME.PHP_EOL);
            static::_apply_final_js();
        }

        static::_set_session_data($utm3, $script);
        return true;
    }

    protected static function _apply_final_js(){
        if ($js = static::get_final_analytics_js()){
            global $PAGE;

            $utm = static::_load_utm_data();
            $js = str_replace(static::JS_PRICE_FIELD, $utm['utm_medium'], $js);
            $js = str_replace(static::JS_CAMPAIGN_FIELD, $utm['utm_campaign'], $js);
            $PAGE->requires->js_amd_inline($js);

            static::_set_param('price_value', $utm['utm_medium']);
            //        $PAGE->requires->js_amd_inline("window.gtag('event', 'payment_page', {'event_category':'page','event_label':'paid', 'event_action':'click'});");
        }
    }

    protected static function validate_base_page($param, $script){
        if (static::has_in_session($param, $script)){
            return false;
        }

        if (!static::is_available_page($script)){
            return false;
        }

        $base_url = static::get_start_page_url();
        if ($base_url != $script && !static::has_in_session($param, $base_url)){
            return false;
        }

        return true;
    }
}