<?php

/**
 * API routines for qtype_pythonrunner
 *
 * @package qtype_pythonrunner
 * @author  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_pythonrunner\constants;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/sqlrunner/lib.php');

const PYTHON_RUNNER = 'qtype_pythonrunner';

const PYTHON_OTHER = 0;

const PYTHON_ALGO = 1;

/**
 * Checks file access for CodeRunner questions.
 *
 * @param stdClass $course        course object
 * @param stdClass $cm            course module object
 * @param stdClass $context       context object
 * @param string   $filearea      file area
 * @param array    $args          extra arguments
 * @param bool     $forcedownload whether or not force download
 * @param array    $options       additional options affecting the file serving
 */
function qtype_pythonrunner_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()){
    global $CFG;
    require_once($CFG->libdir.'/questionlib.php');
    question_pluginfile($course, $context, PYTHON_RUNNER, $filearea, $args, $forcedownload, $options);
}

function pythonrunner_str($name, $a = null){
    return get_string($name, PYTHON_RUNNER, $a);
}

function python_build_result($data, $header = true){
    global $OUTPUT;
    $data = rtrim($data); // we can have spaces in the begin of the string
    if ($data == constants::EMPTY_SET){
        $result = sqlrunner_str('empty_set');
    } else {
        $result = $data;
    }
    return $OUTPUT->render_from_template(PYTHON_RUNNER.'/python_result', [
        'data' => $result,
        'header' => $header,
    ]);
}