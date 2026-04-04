<?php

namespace qtype_sqlrunner;

trait additional_param_trait {
    protected array $_additional_params = [];

    public function add_additional_param($name, $value){
        $this->_additional_params[$name] = $value;
    }

    public function add_additional_params($values){
        foreach ($values as $name => $value){
            $this->add_additional_param($name, $value);
        }
    }

    protected function _get_additional_param($name, $default = false){
        return array_key_exists($name, $this->_additional_params) ? $this->_additional_params[$name] : $default;
    }
}