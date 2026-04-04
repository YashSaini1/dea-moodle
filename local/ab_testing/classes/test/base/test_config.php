<?php

namespace local_ab_testing\test\base;

abstract class test_config {

    protected static $_configuration;

    protected const _CAMPAIGN_URL_VALUE = '${campaign}';
    protected const _MEDIUM_URL_VALUE = '${medium}';
    protected const _SOURCE_URL_VALUE = '${source}';

    /**
     * Do not allow to override field names
     */
    protected const _FIELD_ENABLED = 'enabled';
    protected const _FIELD_PAGES = 'pages';
    protected const _FIELD_METRICS = 'metrics';
    protected const _FIELD_DEFAULT = 'default';
    protected const _FIELD_CAMPAIGN_FIELD = 'campaign_parameter';
    protected const _FIELD_AVAILABLE_CAMPAIGNS = 'available_campaigns';
    protected const _FIELD_FINAL_ANALYTICS_JS = 'final_analytics_js';

    abstract protected static function _load_config();

    public static function get_config(){
        return static::$_configuration;
    }

    public static function get_start_page_url(){
        return array_key_first(static::$_configuration[self::_FIELD_PAGES]);
    }

    public static function get_final_page_url(){
        return array_key_last(static::$_configuration[self::_FIELD_PAGES]);
    }

    public static function get_final_analytics_js(){
        return static::$_configuration[self::_FIELD_FINAL_ANALYTICS_JS] ?? false;
    }

    public static function get_all_pages(){
        return static::$_configuration[self::_FIELD_PAGES];
    }

    public static function is_available_page($page){
        return array_key_exists($page, static::get_all_pages());
    }

    public static function get_all_metrics(){
        return static::$_configuration[self::_FIELD_METRICS];
    }

    public static function get_default_campaign(){
        return static::$_configuration[self::_FIELD_DEFAULT];
    }

    public static function get_campaign_field(){
        return static::$_configuration[self::_FIELD_CAMPAIGN_FIELD];
    }

    public static function get_campaign_parameter(){
        return static::get_all_metrics()[static::$_configuration[self::_FIELD_CAMPAIGN_FIELD]];
    }

    public static function get_available_campaigns(){
        return static::$_configuration[self::_FIELD_AVAILABLE_CAMPAIGNS];
    }

    public static function is_available_campaign($campaign){
        return in_array($campaign, static::get_available_campaigns());
    }

    public static function build_utm_query_url($user_campaign, $page, $medium = null, $source = null){
        $page_parameters = static::get_all_pages()[$page];
        foreach (static::get_all_metrics() as $alias => $metric){
            $page_parameters = str_replace($alias, $metric, $page_parameters);
        }

        $result = str_replace(self::_CAMPAIGN_URL_VALUE, $user_campaign, $page_parameters);
        if (!empty($medium)){
            $result = str_replace(self::_MEDIUM_URL_VALUE, $medium, $result);
        }
        if (!empty($source)){
            $result = str_replace(self::_SOURCE_URL_VALUE, $source, $result);
        }

        return $result;
    }
}