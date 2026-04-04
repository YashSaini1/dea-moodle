<?php
// This file is part of CodeRunner - http://coderunner.org.nz/
//
// CodeRunner is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunner is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunner.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade code for the CodeRunner question type.
 *
 * @param $oldversion the version of this plugin we are upgrading from.
 * @return bool success/failure.
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/sqlrunner/lib.php');

function xmldb_qtype_sqlrunner_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022120101) {
        $table = new xmldb_table('question_sqlrunner_datasets');

        // Adding fields to table question_sqlrunner_datasets.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tables', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10');

        // Adding keys to table question_sqlrunner_datasets.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for question_sqlrunner_datasets.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $DB->execute('UPDATE {question_sqlrunner_options} SET grader="SqlEqualityGrader" WHERE sqlrunnertype="sql"');
    }

    if ($oldversion < 2023031700){
        \theme_sql\mod_quiz\theme_sql_quiz_attempt::delete_old_qa_steps();
        $trans = $DB->start_delegated_transaction();
        $tests = $DB->get_records_select('question_sqlrunner_tests', 'id <> 4 AND id <> 5', null, null, 'id, expected');
        foreach ($tests as $test_info){
            if (empty($test_info->expected)){
                continue;
            }
            $result_data = decode_sql_output($test_info->expected);
            $cutted_data = encode_sql_output($result_data);
            if ($cutted_data != $test_info->expected && $cutted_data != 'null'){
                $test_info->expected = $cutted_data;
                $DB->update_record('question_sqlrunner_tests', $test_info);
            }
        }

        $students_output = $DB->get_records('question_attempt_step_data', ['name' => '_sqloutput'], null, 'id, value');
        foreach ($students_output as $output){
            $result_data = decode_sql_output($output->value);
            $cutted_data = encode_sql_output($result_data);
            if ($cutted_data != $output->value){
                $output->value = $cutted_data;
                $DB->update_record('question_attempt_step_data', $output);
            }
        }
        $trans->allow_commit();
    }

    if ($oldversion < 2023032200){
        $DB->set_field('question_attempt_step_data', 'name', '_runneroutput', ['name' => '_sqloutput']);
        $DB->set_field('question_attempt_step_data', 'name', '_runnererror', ['name' => '_sqlerror']);
    }

    if($oldversion < 2023041000) {
        // Add field videourl in table question_sqlrunner_options
        $table = new xmldb_table('question_sqlrunner_options');
        $field = new xmldb_field('videourl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'prototypeextra');

        // Conditionally launch add field template.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
