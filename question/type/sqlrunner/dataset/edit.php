<?php

/**
 * Edit dataset file
 *
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/question/type/sqlrunner/dataset/lib.php');

$id = optional_param('id', null, PARAM_INT);

$ctx = context_system::instance();
$PAGE->set_context($ctx);
$PAGE->set_url(DATASET_EDIT_URL, ['id' => $id]);

require_login();
require_capability('qtype/sqlrunner:manage_datasets', $ctx);

$options = array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => -1, 'accepted_types' => '.sql');
$dataset_rec = null;
if (!empty($id)){
    $dataset_rec = get_dataset($id);
}

if (!empty($dataset_rec)){
    // we edit by one dataset
    $dataset_name = 'dataset[0]';
    $tables_name = 'dataset_tables[0]';
    $draftitemid = file_get_submitted_draft_itemid($dataset_name);
    file_prepare_draft_area($draftitemid, $ctx->id, SQL_RUNNER, 'dataset', $dataset_rec->id);

    $dataset = new stdClass;
    $dataset->id = $dataset_rec->id;
    $dataset->$dataset_name = $draftitemid;
    $dataset->$tables_name = $dataset_rec->tables;
    $heading = 'edit_dataset';
} else {
    $dataset = new stdClass;
    $dataset->id = null;
    $heading = 'add_datasets';
}

$title = sqlrunner_str($heading);
$PAGE->set_heading($title);
$PAGE->set_title($title);

$mform = new qtype_sqlrunner\form\dataset_manager_form(null, ['dataset_rec' => $dataset]);
$mform->set_data($dataset);

if ($mform->is_cancelled()){
    redirect(DATASET_LIST_URL);
} elseif ($data = $mform->get_data()) {
    $data->files = [];
    foreach ($data->dataset as $key => $draftitemid){
        $files = $mform->get_uploaded_files($draftitemid);
        if (!empty($files)){
            $data->files[$draftitemid] = array_shift($files);
        }
    }
    $data->files_tables = $mform->get_files_tables();
    if (!save_dataset($data, $options)){
        throw new moodle_exception('exception_cannot_create_or_update_dataset', SQL_RUNNER);
    }
    $msg = '';
    if (empty($data->id)){
        $msg = sqlrunner_str('dataset_created', count($data->files));
    } else {
        $msg = sqlrunner_str('dataset_updated');
    }
    redirect(DATASET_LIST_URL, $msg, 0, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();