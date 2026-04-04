<?php

namespace local_data_collector;

class webhook_processor {

    public static function process_moodle_event($data){
        $task = new \local_data_collector\task\process_event();
        $task->set_custom_data($data);
        \core\task\manager::queue_adhoc_task($task);
    }

    public static function process_webhook_event(moodle_event_dto $eventdata){
        $webhook_event = webhook_event_dto::create_from_custom_data($eventdata);
        if (!static::_send_to_webhook($webhook_event)){
            static::_save_local_event($webhook_event);
        }
    }

    protected static function _save_local_event(webhook_event_dto $webhook_event){
        global $CFG;
        $dir = $CFG->dirroot.'/local/data_collector/webhook/'.$webhook_event->type;
        if (!is_dir($dir) && !mkdir($dir)){
            return;
        }
        $filename = $dir.'/'.$webhook_event->date.'.json';
        if (!file_exists($filename)){
            $encoded_event = json_encode([$webhook_event]);
            file_put_contents($filename, $encoded_event);
            static::_log_resulted_data($encoded_event);
            return;
        }
        $data = json_decode(file_get_contents($filename), 1);
        $data[] = $webhook_event;
        file_put_contents($filename, json_encode($data));
        static::_log_resulted_data(json_encode($webhook_event));
    }

    protected static function _send_to_webhook(webhook_event_dto $webhook_event){
        $webhook_url = core::get_config('webhook_url');
        if (empty($webhook_url)){
            return false;
        }

        try {
            [$code, $result, $error] = static::_send_data($webhook_url, $webhook_event);
            if (!empty($error)){
                mtrace($error);
                return false;
            }

            if ($code < 200 || $code > 300){
                mtrace('Bad request. Status code: '.$code);
                return false;
            }
        } catch (\Throwable $t){
            mtrace($t->getMessage());
            return false;
        }

        return true;
    }

    protected static function _send_data(string $url, webhook_event_dto $event){
        $encoded_event = json_encode($event);
        $curl_opt = [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $encoded_event,
        ];

        static::_log_resulted_data($encoded_event);
        $ch = curl_init($url);
        curl_setopt_array($ch, $curl_opt);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$statuscode, $result, $error];
    }

    protected static function _log_resulted_data($data){
        mtrace('Sended: ' . $data);
    }
}