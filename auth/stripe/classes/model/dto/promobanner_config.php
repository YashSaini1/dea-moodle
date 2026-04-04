<?php

namespace auth_stripe\model\dto;

use auth_stripe\core;

class promobanner_config {

    const PROMOBANNER_CONFIG = 'promobanner:';

    public $enabled = false;

    public $blackfriday = false;

    public $text;
    public $text_short;

    public $couponname;

    public $duration;
    public $duration_period;

    protected function __construct($init = true){
        if ($init){
            $this->initialize();
        }
    }

    public static function get_instance(){
        static $instance = null;
        if (!$instance){
            $instance = new static();
        }
        return $instance;
    }


    public function initialize(){
        $config = get_config(core::PLUGIN_NAME);
        $fields = get_class_vars(static::class);
        foreach ($fields as $field => $value){
            $fieldname = static::PROMOBANNER_CONFIG.$field;
            if (property_exists($config, $fieldname)){
                $this->$field = $config->$fieldname;
            }
        }
    }

    public static function get_default(){
        $instance = new static(false);
        $instance->enabled = false;
        $instance->blackfriday = false;
        $instance->text = 'Get premium with discount, use the coupon';
        $instance->text_short = 'Get premium with discount';
        $instance->couponname = '';
        $instance->duration = 3;
        $instance->duration_period = core::PERIOD_DAY;
        return $instance;
    }

    public function save(){
        $fields = get_class_vars(static::class);
        foreach ($fields as $field => $value){
            set_config(static::PROMOBANNER_CONFIG.$field, $this->$field, core::PLUGIN_NAME);
        }
    }

    public function get_due_time($timestamp){
        return strtotime('+'.$this->duration.' '.$this->duration_period, $timestamp);
    }
}