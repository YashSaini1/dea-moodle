<?php

namespace local_data_collector;

use \auth_stripe\core as stripe;

class core extends \local_sql\core {

    const PLUGIN_NAME = 'local_data_collector';
    const PLUGIN_PATH = 'local/data_collector';

    const EVENT_TYPE_LOGIN = 'login';
    const EVENT_TYPE_SIGNUP = 'signup';
    const EVENT_TYPE_PAYMENT = 'payment';
    const EVENT_TYPE_CANCEL_TIER = 'cancel_tier';

    const PRICE_TYPE_SUBSCRIPTION = 'subscription';
    const PRICE_TYPE_ONE_TIME = stripe::PERIOD_ONE_TIME;

    public static function is_collecting_enabled(){
        static $result = null;
        if (is_null($result)){
            $result = !empty(static::get_config('enable'));
        }
        return $result;
    }
}