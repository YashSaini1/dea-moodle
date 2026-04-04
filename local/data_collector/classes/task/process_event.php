<?php

namespace local_data_collector\task;

use \local_data_collector\core;
use local_data_collector\moodle_event_dto;
use local_data_collector\webhook_processor;

/**
 * Process and send event data task
 */
class process_event extends \core\task\adhoc_task {

    public function get_name(){
        return core::str('task:process_event');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        $event_data = (array)$this->get_custom_data();
        $moodle_event = new moodle_event_dto($event_data);
        webhook_processor::process_webhook_event($moodle_event);
    }
}