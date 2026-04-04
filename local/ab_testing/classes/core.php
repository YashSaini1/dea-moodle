<?php

namespace local_ab_testing;

class core extends \local_sql\core {

    const PLUGIN_NAME = 'local_ab_testing';

    const PROFILE_FIELD_NAME = 'ab_testing';

    const PLUGIN_PATH = 'local/ab_testing';

    public static function is_ab_enabled(){
        static $result = null;
        if (is_null($result)){
            $result = !empty(static::get_config('enable'));
        }
        return $result;
    }
}