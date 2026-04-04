<?php

namespace local_sql\moodle;

class course_customfield {

    const FREE_QUESTION_FIELD = 'number_free_question';

    const COACHING_COURSE_FIELD = 'is_coaching_course';

    static public function get_custom_data($fieldname, $cid, $default = 0){
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $datas = $handler->get_instance_data($cid);
        foreach ($datas as $data){
            if ($data->get_field()->get('shortname') === $fieldname){
                return $data->get_value();
            }
        }
        return $default;
    }

    static public function get_number_of_free_questions($courseid, $default = 0){
        return static::get_custom_data(static::FREE_QUESTION_FIELD, $courseid, $default);
    }

    static public function get_is_coaching_course($courseid, $default = 0){
        return static::get_custom_data(static::COACHING_COURSE_FIELD, $courseid, $default);
    }

    static public function get_field_instance($fieldname){
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $fields = $handler->get_fields();
        foreach ($fields as $field){
            if ($field->get('shortname') == $fieldname){
                return $field;
            }
        }
        return null;
    }
}