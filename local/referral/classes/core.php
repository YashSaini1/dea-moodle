<?php

namespace local_referral;

use Exception;
use local_referral\core\logger;
use local_referral\core\moodle;

class core {
    use logger, moodle;

    const LOCAL_REFERRAL = 'local_referral';

    const PLUGIN_NAME = self::LOCAL_REFERRAL;
    const PLUGIN_PATH = 'local/referral';

    /**
     * @throws Exception
     */
    public static function log_message($message) {
        self::log_text($message);
    }

    /**
     * @throws Exception
     */
    public static function track_time($operationName) {
        if (!isset(self::$timers[$operationName])) {
            self::start_timer($operationName);
        } else {
            self::stop_timer($operationName);
        }
    }
}