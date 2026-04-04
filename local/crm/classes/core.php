<?php

namespace local_crm;

use Exception;
use local_crm\core\logger;
use local_crm\core\moodle;

class core {
    use logger, moodle;

    const LOCAL_CRM = 'local_crm';

    const PLUGIN_NAME = self::LOCAL_CRM;
    const PLUGIN_PATH = 'local/crm';

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