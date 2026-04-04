<?php

/**
 * Lib file for questions dataset
 *
 * @package qtype_sqlrunner
 * @author  2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/sqlrunner/lib.php');
require_once($CFG->libdir.'/filelib.php');

function get_dataset($id){
    static $datasets = [];
    if (empty($id)){
        return false;
    }
    if (!isset($datasets[$id])){
        global $DB;
        $datasets[$id] = $DB->get_record(DATASET_TABLE, ['id' => $id]);
    }

    return $datasets[$id];
}

function get_dataset_file($id){
    global $DB;
    return $DB->get_record_select('files',
        'itemid = ? AND filename NOT LIKE "." AND component=? AND filearea=?', [$id, SQL_RUNNER, 'dataset']);
}

function get_datasets(){
    global $DB;
    return $DB->get_records_sql("SELECT d.*, f.filename
        FROM {question_sqlrunner_datasets} d 
        JOIN {files} f ON f.itemid = d.id AND f.component=? AND f.filearea=?
        WHERE f.filename NOT LIKE '.'", [SQL_RUNNER, 'dataset']);
}

function check_dataset_table_exists($table, $id){
    static $tables = [];
    $id = empty($id) ? 0 : $id;
    if (!isset($tables[$table])){
        global $DB;
        $where = $DB->sql_like('tables', '?');
        $tables[$table] = $DB->record_exists_select(DATASET_TABLE, "$where AND id != ?", [$table, $id]);
    }
    return $tables[$table];
}

/**
 * This function create or update dataset.
 *
 * **This function was written in 5 minutes and may contains bugs or bad logic**
 *
 * @param object $form_data
 * @param array  $options
 *
 * @return bool
 */
function save_dataset($form_data, $options){
    global $DB;
    $ctx = context_system::instance();
    $db_worker = qtype_sqlrunner\sqlrunner_database_worker::get_instance(null, true);
    $mysqli = $db_worker->init_connection();
    $db_worker->use_db($mysqli);
    $db_worker->start_transaction($mysqli);

    try {
        // if isset $form_data->id, we edit dataset.
        if (!empty($form_data->id)){
            $file_rec = get_dataset_file($form_data->id);
            /**
             * @var stored_file $uploaded_file
             */
            $uploaded_file = reset($form_data->files);
            $dataset = get_dataset($form_data->id);

            // check files timemodified field. If they different - user update the file. Delete previous tables
            // if files are not changed, fo not update anything
            if ($file_rec->timemodified == $uploaded_file->get_timemodified()){
                $db_worker->rollback_transaction($mysqli);
                $db_worker->close_mysqli($mysqli);
                return true;
            }
            $db_worker->delete_tables($dataset->tables);
        }

        $mysqli = $db_worker->process_uploaded_files($form_data->files, $mysqli, array_values($form_data->files_tables));
        if (!$mysqli){
            throw new moodle_exception('exception_failed_create_files_tables', SQL_RUNNER);
        }

        if (empty($form_data->id)){
            foreach ($form_data->files_tables as $draftid => $tables){
                $recordid = $DB->insert_record(DATASET_TABLE, [
                    'tables'      => implode(',', $tables),
                    'timecreated' => time(),
                ]);
                file_save_draft_area_files($draftid, $ctx->id, SQL_RUNNER, 'dataset', $recordid, $options);
            }
        } else {
            // only 1 iteration here
            foreach ($form_data->files_tables as $draftid => $tables){
                $updated = (object)array(
                    'id'          => $form_data->id,
                    'tables'      => implode(',', $tables),
                    'timecreated' => time(),
                );
                $DB->update_record(DATASET_TABLE, $updated);
                file_save_draft_area_files($draftid, $ctx->id, SQL_RUNNER, 'dataset', $form_data->id, $options);
            }
        }
        $db_worker->commit_transaction($mysqli, false);
    } catch (Exception $e){
        if ($mysqli->thread_id){
            $db_worker->rollback_transaction($mysqli, false);
            $db_worker->close_mysqli($mysqli);
        }
        throw $e;
    }

    $db_worker->close_mysqli($mysqli);
    return true;
}