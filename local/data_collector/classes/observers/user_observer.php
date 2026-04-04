<?php

namespace local_data_collector\observers;

use core\event\user_created;
use core\event\user_loggedin;
use local_data_collector\core;
use local_data_collector\moodle_event_dto;
use local_data_collector\webhook_processor;
use local_sql\moodle\role_manager;

/**
 * Observer for user events
 *
 * @package     local_data_collector
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
            case user_loggedin::class:
                static::_user_loggedin($event);
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
        $data = new moodle_event_dto([
            'userid' => $event->objectid,
            'type' => core::EVENT_TYPE_SIGNUP
        ]);
        webhook_processor::process_moodle_event($data);
        return true;
    }

    /**
     * @param user_loggedin $event
     *
     * @return bool
     */
    protected static function _user_loggedin(user_loggedin $event){
        if (role_manager::is_admin($event->objectid)){
            return false;
        }

        $data = new moodle_event_dto([
            'userid' => $event->objectid,
            'type' => core::EVENT_TYPE_LOGIN
        ]);
        webhook_processor::process_moodle_event($data);
        return true;
    }
}