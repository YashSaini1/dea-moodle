<?php

namespace local_data_collector\observers;

use local_data_collector\core;

/**
 * Base observer class for all collected events
 *
 * @package     local_data_collector
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class data_collector {

    public static function process_event(\core\event\base $event){
        return core::is_collecting_enabled();
    }
}