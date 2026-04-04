<?php

namespace local_sql\lib;

use local_sql\core;

class documentation {

    public static function render_button_from_cm($cm, $primary = false){
        global $DB;
        if (!$cm->modname){
            throw new \Exception('Missing modname property!');
        }

        $cm_record = $DB->get_record($cm->modname, ['id' => $cm->instance]);
        return static::render_button_from_instance($cm_record, $cm->id, $primary);
    }

    public static function render_button_from_instance($cm_instance, $cmid, $primary = false){
        if ($cm_instance && $cm_instance->intro){
            return static::render_button($cmid, $primary);
        }

        return '';
    }

    public static function render_button($cmid, $primary = false){
        $link = new \moodle_url('/local/sql/video_documentation.php', ['id' => $cmid]);
        return core::render_from_template('documentation_button', ['link' => $link, 'primary' => $primary]);
    }
}