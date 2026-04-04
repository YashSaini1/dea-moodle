<?php

namespace local_ab_testing\test\base;

use local_ab_testing\core;

trait session_manager {

    protected static \stdClass $_session;

    abstract public static function _get_test_name();

    public static function init(){
        global $SESSION;

        if (!property_exists($SESSION, 'ab_testing')){
            $SESSION->ab_testing = new \stdClass();
        }

        if (isloggedin()){
            global $USER, $CFG;
            require_once($CFG->dirroot.'/user/profile/lib.php');

            $fields = profile_get_user_fields_with_data($USER->id);
            foreach ($fields as $formfield){
                if ($formfield->get_shortname() == core::PROFILE_FIELD_NAME){
                    $SESSION->ab_testing = (object)json_decode($formfield->data, 1);
                    break;
                }
            }
        }
        static::$_session = &$SESSION->ab_testing;
        static::$_session->{static::_get_test_name()} = static::$_session->{static::_get_test_name()} ?? [];
    }

    protected static function _set_param($param, $value){
        static::$_session->{static::_get_test_name()}[$param] = $value;
    }

    protected static function _set_subparam($param, $sub_param, $value){
        static::$_session->{static::_get_test_name()}[$param][$sub_param] = $value;
    }

    protected static function _get_param($param){
        return static::$_session->{static::_get_test_name()}[$param] ?? null;
    }

    protected static function _is_param($param){
        return array_key_exists($param, static::$_session->{static::_get_test_name()});
    }

    /**
     * @param string $param
     * @param string $script this path must be cleaned from utm params
     *
     * @return bool
     */
    public static function has_in_session(string $param, string $script): bool{
        if (!static::_is_param($param)){
            return false;
        }

        return !empty(static::_get_param($param)[$script]);
    }

    protected static function _set_session_data($campaign, $script){
        static::_set_campaign($campaign);
        static::set_url($campaign, $script);
        static::update_profile_data();
    }

    public static function set_url($campaign, $script){
        if (!static::_is_param($campaign)){
            static::_set_param($campaign, []);
        }

        static::_set_subparam($campaign, $script, true);
    }

    protected static function _set_campaign($campaign){
        if (static::user_has_campaign()){
            return;
        }

        $campaign_name = static::get_campaign_parameter();
        static::_set_param($campaign_name, $campaign);
    }

    public static function get_user_campaign(){
        return static::_get_param(static::get_campaign_parameter()) ?? null;
    }

    public static function user_has_campaign(){
        return !empty(static::get_user_campaign());
    }

    public static function update_profile_data($userid = null, $created_event = false){
        if (!isloggedin() && !$created_event){
            return;
        }

        $userid = core::get_userid($userid);
        $fields = profile_get_user_fields_with_data($userid);
        foreach ($fields as $formfield){
            if ($formfield->get_shortname() == core::PROFILE_FIELD_NAME){
                $session_data = static::$_session;
                $data = (object)[
                    'id'                  => $userid,
                    $formfield->inputname => !empty($session_data) ? json_encode($session_data) : '',
                ];
                $formfield->edit_save_data($data);
                break;
            }
        }
    }
}