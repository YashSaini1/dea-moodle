<?php

namespace local_ab_testing\observers;

use core\event\user_created;
use local_ab_testing\test\base\base_test;

/**
 * Observer for user events
 *
 * @package     local_ab_testing
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class user_observer extends data_collector {

    public static function process_event(\core\event\base $event){
        if (!parent::process_event($event)){
            return false;
        }

        switch (get_class($event)){
            case user_created::class:
                static::_user_created($event);
                break;
        }
        return true;
    }

    /**
     * @param user_created $event
     *
     * @return bool
     */
    protected static function _user_created(user_created $event){
        base_test::trigger_hook('user_created', $event->objectid);
        return true;
    }
}