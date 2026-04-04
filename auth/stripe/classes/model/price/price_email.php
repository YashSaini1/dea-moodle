<?php

namespace auth_stripe\model\price;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use lang_string;

class price_email extends price_model {

    static protected string $table = stripe_database::TABLE_PRICE_EMAIL;

    public int $id;
    public int $priceid;
    public string $email_text;

    public function out($a = null){
        return $this->_get_string($a);
    }

    /**
     * {@see core_string_manager_standard::get_string()}
     *
     * @param $a
     *
     * @return array|string|string[]
     */
    protected function _get_string($a){
        $string = $this->email_text;
        if ($a === null){
            return $string;
        }

        // Process array's and objects (except lang_strings).
        if (is_array($a) or (is_object($a) && !($a instanceof lang_string))){
            $a = (array)$a;
            $search = array();
            $replace = array();
            foreach ($a as $key => $value){
                if (is_int($key)){
                    // We do not support numeric keys - sorry!
                    continue;
                }
                if (is_array($value) or (is_object($value) && !($value instanceof lang_string))){
                    // We support just string or lang_string as value.
                    continue;
                }
                $search[] = '{$a->'.$key.'}';
                $replace[] = (string)$value;
            }
            if ($search){
                $string = str_replace($search, $replace, $string);
            }
        } else {
            $string = str_replace('{$a}', (string)$a, $string);
        }

        return $string;
    }

    public static function get_default($a){
        return core::str('newusernewspecialoffer', $a);
    }
}