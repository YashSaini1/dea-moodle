<?php

namespace auth_stripe\util;

class PhoneNumberFormDTO {

    protected $_fieldid;

    protected $_hidden;

    protected $_value;

    /**
     * @param $_fieldid
     * @param $_hidden
     * @param $_value
     */
    public function __construct($_fieldid, $_hidden, $_value){
        $this->_fieldid = $_fieldid;
        $this->_hidden = $_hidden;
        $this->_value = $_value;
    }

    /**
     * @return mixed
     */
    public function get_fieldid(){
        return $this->_fieldid;
    }

    /**
     * @return mixed
     */
    public function get_hidden(){
        return $this->_hidden;
    }

    /**
     * @return mixed
     */
    public function get_value(){
        return $this->_value;
    }

    /**
     * @param mixed $value
     */
    public function set_value($value): void{
        $this->_value = $value;
    }

}