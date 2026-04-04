<?php

namespace local_sql;

/**
 * @method static analytics is_enabled()
 * @method static analytics get_settings()
 * @method static analytics apply()
 */
class analytics {

    /// Static block
    protected static $_instance;

    public static function getInstance(){
        if (empty(static::$_instance)){
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    public static function __callStatic($name, $arguments){
        $instance = static::getInstance();
        $name = '_'.$name;
        if (method_exists($instance, $name)){
            return $instance->$name($arguments);
        }
        throw new \Exception('Call to undefined method');
    }

    /// Instance block

    protected $_config;

    protected function __construct(){
        $this->_config = (object) [
            'enabled' => core::get_config('analytics_enabled'),
            'settings' => core::get_config('analytics_settings'),
        ];
    }

    public function _is_enabled(){
        return $this->_config->enabled;
    }

    public function _get_settings(){
        return !empty($this->_config->settings) ? json_decode($this->_config->settings, 1) : false;
    }

    public function _apply(){
        global $SCRIPT;
        $settings = $this->_get_settings();
        if (empty($settings)){
            return '';
        }
        $result = '';
        foreach ($settings['rules'] as $rule => $script_key){
            if (preg_match('/'.$rule.'/', $SCRIPT)){
                $result .= $settings['scripts'][$script_key];
            }
        }
        return $result;
    }
}