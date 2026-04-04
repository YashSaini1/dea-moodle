<?php

namespace local_sql\core\model;

trait config {

    public function set_config($config){
        $this->_before_init($config);
        if (!empty($config)){
            foreach ($config as $field => $value){
                $this->$field = $value;
            }
        }
        $this->_after_init();
    }

    protected function _before_init($config = []){ }

    protected function _after_init(){ }
}