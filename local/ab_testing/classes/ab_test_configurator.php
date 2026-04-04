<?php

namespace local_ab_testing;

class ab_test_configurator {

    /**
     * Get A/B testing configuration
     *
     * @return array
     */
    public static function get_settings(){
        static $result = null;
        if (is_null($result)){
            $result = core::get_config('ab_settings');
//            $result = preg_replace('/\s/', '', $result);
            $result = !empty($result) ? json_decode($result, 1) : [];
        }
        return $result;
    }
}