<?php

/**
 * Data modeling lib file
 *
 * @package    qtype_data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

const DATA_MODELING = 'qtype_data_modeling';

/**
 * Checks file access for data_modeling questions.
 *
 * @package  qtype_data_modeling
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function qtype_data_modeling_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;
    require_once($CFG->libdir.'/questionlib.php');

    if ($filearea == 'generalfeedback'){
        require_login($course, false, $cm);

        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/question/$filearea/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()){
            send_file_not_found();
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    question_pluginfile($course, $context, DATA_MODELING, $filearea, $args, $forcedownload, $options);
}

function dm_str($name, $a = null){
    return get_string($name, DATA_MODELING, $a);
}

function qtype_data_modeling_format_answer($question, $courseid){
    $context = context_course::instance($courseid);
    $options = array('noclean' => true, 'para' => false, 'filter' => false, 'context' => $context, 'overflowdiv' => true);
    $intro = file_rewrite_pluginfile_urls($question->generalfeedback, 'pluginfile.php', $context->id, DATA_MODELING, 'generalfeedback', $question->id);
    return trim(format_text($intro, $question->generalfeedbackformat, $options, null));
}