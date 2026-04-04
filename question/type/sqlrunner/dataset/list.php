<?php

/**
 * File display list of all datasets
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

require_login();
require_capability('qtype/sqlrunner:manage_datasets', $ctx);

$add_btn = html_writer::link(new moodle_url(DATASET_EDIT_URL), sqlrunner_str('add_dataset'), ['class' => 'btn btn-primary']);
$PAGE->set_button($add_btn);
$PAGE->set_url(DATASET_LIST_URL);
$title = sqlrunner_str('manage_datasets');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$table = new html_table();
$table->head = [
    '№',
    sqlrunner_str('tables'),
    sqlrunner_str('filename'),
    '',
    '',
];

$cell = fn($text) => new html_table_cell($text);
$link = fn($url, $text, $attr = null) => $cell(html_writer::link($url, $text, $attr));

$datasets = get_datasets();
$i = 1;
foreach ($datasets as $dataset){
    $number = $cell($i++);
    $tables = $cell($dataset->tables);
    $filename = $cell($dataset->filename);

    $action_edit = $link(
        new moodle_url(DATASET_EDIT_URL, ['id' => $dataset->id]),
        get_string('edit')
    );
    $action_delete = $link(
        new moodle_url(DATASET_DELETE_URL, ['id' => $dataset->id]),
        get_string('delete')
    );

    $table->data[] = new html_table_row([
        $number,
        $tables,
        $filename,
        $action_edit,
        $action_delete,
    ]);
}

echo $OUTPUT->header();
echo html_writer::table($table);
echo $OUTPUT->footer();