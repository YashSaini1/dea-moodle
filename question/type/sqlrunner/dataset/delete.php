<?php

/**
 * Delete dataset action
 *
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/question/type/sqlrunner/dataset/lib.php');

$id = optional_param('id', null, PARAM_INT);
$force = optional_param('force', false, PARAM_INT);

$ctx = context_system::instance();
$PAGE->set_context($ctx);
$PAGE->set_url(DATASET_DELETE_URL, ['id' => $id]);
$PAGE->set_title('Delete dataset');

require_login();
require_capability('qtype/sqlrunner:manage_datasets', $ctx);

if (empty($id)){
    throw new moodle_exception('exception_invalid_dataset_id', SQL_RUNNER, DATASET_LIST_URL);
}
$dataset_rec = get_dataset($id);
if (empty($dataset_rec)){
    throw new moodle_exception('exception_invalid_dataset_id', SQL_RUNNER, DATASET_LIST_URL);
}

$db_worker = \qtype_sqlrunner\sqlrunner_database_worker::get_instance(null, true);
$result = $db_worker->delete_tables($dataset_rec->tables);
if ($result->is_error() && !$force){
    $force_delete = html_writer::link(new moodle_url(DATASET_DELETE_URL, ['id' => $id, 'force' => true]), sqlrunner_str('dataset_can_force_delete'));
    $msg = sqlrunner_str('exception_cannot_delete_file', ['error' => $result->error, 'link' => $force_delete]);
    redirect(DATASET_LIST_URL, $msg, 0, \core\output\notification::NOTIFY_ERROR);
}

$fs = get_file_storage();
$fs->delete_area_files($ctx->id, SQL_RUNNER, 'dataset', $id);

$DB->delete_records(DATASET_TABLE, ['id' => $id]);

redirect(DATASET_LIST_URL);