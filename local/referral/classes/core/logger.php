<?php

namespace local_referral\core;

use Exception;

trait logger {

    protected static array $options = [
        'dateFormat' => 'Y-M-d',
        'logFormat'  => 'H:i:s d-M-Y'
    ];

    protected static array $timers = [];

    /**
     * @throws Exception
     */
    protected static function open_file() {
        global $CFG;
        $time = date(self::$options['dateFormat']);
        $logsDir = $CFG->dirroot."/".static::PLUGIN_PATH."/logs";
        $logFile = $logsDir . "/$time-log.txt";

        if (!is_dir($logsDir)) {
            if (!mkdir($logsDir, 0777, true) && !is_dir($logsDir)) {
                throw new Exception("ERROR: Unable to create logs directory: $logsDir");
            }
        }

        $file = fopen($logFile, 'a');
        if (!$file) {
            throw new Exception("ERROR: Unable to create log file: $logFile");
        }


        if (!is_writable($logFile)) {
            throw new Exception("ERROR: Log file is not writable: $logFile");
        }

        return $file;
    }

    /**
     * @throws Exception
     */
    protected static function log_text($message) {
        try {
            $time = date(self::$options['logFormat']);
            $file = self::open_file();
            fwrite($file, "[$time] : $message" . PHP_EOL);
        } catch (Exception $e) {
            error_log("Error writing to log: " . $e->getMessage());
        } finally {
            if (isset($file)) {
                fclose($file);
            }
        }
    }

    protected static function start_timer($timer_name) {
        self::$timers[$timer_name] = microtime(true);
    }

    /**
     * @throws Exception
     */
    protected static function stop_timer($timer_name) {
        if (!isset(self::$timers[$timer_name])) {
            self::log_text("$timer_name not started.");
            return;
        }

        $end_time = microtime(true);
        $elapsed_time = $end_time - self::$timers[$timer_name];

        $log_message =  "$timer_name elapsed time: "  . number_format($elapsed_time, 4) . " s.";

        self::log_text($log_message);

        unset(self::$timers[$timer_name]);
    }

}

