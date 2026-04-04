<?php

/**
 * API routines for qtype_sqlrunner
 *
 * @package qtype_sqlrunner
 * @author  2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_sqlrunner\constants;

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot . '/theme/sql/lib.php');
require_once $CFG->dirroot . '/question/type/sqlrunner/vendor/autoload.php';
require_once $CFG->dirroot . '/question/type/sqlrunner/vendor_coderunner/autoload.php';

const SQL_RUNNER = 'qtype_sqlrunner';

const DATASET_TABLE = 'question_sqlrunner_datasets';

const DATASET_EDIT_URL = '/question/type/sqlrunner/dataset/edit.php';
const DATASET_LIST_URL = '/question/type/sqlrunner/dataset/list.php';
const DATASET_DELETE_URL = '/question/type/sqlrunner/dataset/delete.php';

const ROWS_LIMIT = 100;
const COLUMNS_LIMIT = 100;

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
function qtype_sqlrunner_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()){
    global $CFG;
    require_once($CFG->libdir.'/questionlib.php');
    question_pluginfile($course, $context, 'qtype_sqlrunner', $filearea, $args, $forcedownload, $options);
}

function sqlrunner_str($name, $a = null){
    return get_string($name, 'qtype_sqlrunner', $a);
}

function render_submission_table($result_data){
    global $OUTPUT;
    $data = decode_sql_output($result_data);
    if (empty($data)){
        return '';
    }

    $fields_count = count($data['fields']);
//    $data_count = count($data['data']); // always less then 100
    if ($fields_count > COLUMNS_LIMIT ){
        $data = cut_sql_output_data($data);
        $fields_count = count($data['fields']);
//        $data_count = count($data['data']);
    }

    $result = [
        'fields'       => $data['fields'],
        'count_fields' => $fields_count,
        'data'         => [],
        'has_data'     => !empty($data['data']) && $data['data'] != constants::EMPTY_SET,
    ];
    if ($result['has_data']){
        $rendered_data = '';
        foreach ($data['data'] as $table_row){
            $row = '<tr class="result_row">';
            foreach ($table_row as $cell){
                $row .= '<td class="cell">'.$cell.'</td>';
            }
            $rendered_data .= $row.'</tr>';
        }
        $result['data'] = $rendered_data;
    }
    return $OUTPUT->render_from_template('qtype_sqlrunner/result_table', $result);
}

/**
 * When you attempt to save big student sql result, testing_outcome object was serialised and all serialised data will be snip.
 * This function uses gzip to compress big data string.
 * But it is not safety and some data cannot bne uncompress.
 * json_encode used as temporary solution.
 *
 * This function encode full query result.
 *
 * @param array{fields:array, data:array} $data
 *
 * @return false|string encoded string
 */
function encode_sql_output($data, $cut_data = true){
    if ($cut_data){
        $data = cut_sql_output_data($data);
    }
    return json_encode($data);
    $output_fields = sqlrunner_encode($data['fields']);
    $output_data = [];
    foreach ($data['data'] as $data_rec){
        $output_data[] = sqlrunner_encode($data_rec);
    }

    $result = [
        'fields' => $output_fields,
        'data'   => $output_data,
    ];

    return sqlrunner_encode($result, false);
}

/**
 * @param array{fields:array, data:array} $data
 *
 * @return array
 */
function cut_sql_output_data($data){
    if ($data['data'] != constants::EMPTY_SET && count($data['data']) > ROWS_LIMIT){
        $result_data = [];
        // Non-associative array here, so we can use key as record position
        foreach ($data['data'] as $key => $record){
            if ($key < ROWS_LIMIT){
                $result_data[] = $record;
            }
        }
        $data['data'] = $result_data;
    }

    $count_fields = count($data['fields']);
    if ($count_fields > COLUMNS_LIMIT){
        // remove limited columns here if it's rational
        if ($count_fields / COLUMNS_LIMIT < 2){
            for ($i = COLUMNS_LIMIT + 1; $i < $count_fields; $i++){
                unset($data['fields'][$i]);
            }

            foreach ($data['data'] as $record){
                for ($i = COLUMNS_LIMIT + 1; $i < $count_fields; $i++){
                    unset($record[$i]);
                }
            }
        } else {
            // rebuild all records
            $result_fields = [];
            foreach ($data['fields'] as $key => $field){
                if ($key < COLUMNS_LIMIT){
                    $result_fields[] = $field;

                }
            }
            $data['fields'] = $result_fields;

            // Non-associative array here, so we can use key as record position
            foreach ($data['data'] as $key => $record_info){
                $updated_data = [];
                foreach ($record_info as $field_key => $field_value){
                    if ($field_key < COLUMNS_LIMIT){
                        $updated_data[] = $field_value;
                    }
                }
                $data['data'][$key] = $updated_data;
            }
        }
    }
    return $data;
}

/**
 * Encode part data
 *
 * @param array $data
 * @param bool  $encode_level
 *
 * @return false|string
 */
function sqlrunner_encode($data, $encode_level = true){
    // All parts of data zipped by gzip with 0 compress level
    // But final result compressed with 9 level.
    // This combination contains 3 times less symbols than in any other
    return utf8_encode(gzencode(json_encode($data), $encode_level ? 0 : 9));
}

/**
 * Decode encoded sql query result.
 *
 * json_decode used as temporary solution.
 *
 * @param string $encoded
 *
 * @return array{fields:array, data:array}
 */
function decode_sql_output($encoded){
    return json_decode($encoded, 1);
    $encoded = sqlrunner_decode($encoded);
    if (!$encoded) return false;

    $encoded['fields'] = sqlrunner_decode($encoded['fields']);
    $decoded_data = [];
    foreach ($encoded['data'] as $encoded_rec){
        $decoded_data[] = sqlrunner_decode($encoded_rec);
    }
    $encoded['data'] = $decoded_data;
    return $encoded;
}

/**
 * Decode functions.
 * Do not use compress levels here, because gzip already knows it
 *
 * @param string $encoded_data
 *
 * @return array
 */
function sqlrunner_decode($encoded_data){
    return json_decode(gzdecode(utf8_decode($encoded_data)), 1);
}

function sql_runner_is_student($userid = null){
    return \local_sql\moodle\role_manager::is_student($userid);
}

function check_subclass($child, $parent){
    return is_a($child, $parent, true);
}