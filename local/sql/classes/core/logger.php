<?php

namespace local_sql\core;

use \local_sql\log\file_logger;

trait logger {
    /**
     * @param string $plugin_path
     *
     * @return file_logger
     */
    public static function get_logger(string $plugin_path): file_logger{
        global $CFG;
        $path = $CFG->dirroot.'/'.$plugin_path;
        return file_logger::getInstance($path);
    }

    public static function info($message, $context = []){
        static::get_logger(static::PLUGIN_PATH)::info($message, $context);
    }

    public static function debug($message, $context = []){
        static::get_logger(static::PLUGIN_PATH)::debug($message, $context);
    }

    public static function error($message, $context = []){
        static::get_logger(static::PLUGIN_PATH)::error($message, $context);
    }

    public static function fatal($message, $context = []){
        static::get_logger(static::PLUGIN_PATH)::fatal($message, $context);
    }
}