<?php

namespace local_data_collector;

use local_sql\core\model\config;

class moodle_event_dto {
    use config;

    public $userid;
    public $type;
    public $date;
    public $data;

    public function __construct($config = []){
        $this->date = time();
        $this->set_config($config);
    }
}