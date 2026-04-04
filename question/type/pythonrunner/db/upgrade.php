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

require_once($CFG->dirroot . '/question/type/pythonrunner/lib.php');

function xmldb_qtype_pythonrunner_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023032800){
        $table = new xmldb_table('question_python_options');
        $fields = ['pertesttemplate', 'enablecombinator', 'showsource', 'allornothing'];
        foreach ($fields as $fieldname){
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)){
                $dbman->drop_field($table, $field);
            }
        }
        $field = new xmldb_field('pythontype', XMLDB_TYPE_INTEGER, 2, true, false, false, null, 'prototypetype');
        if (!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2023032801) {
        // Define table question_python_sqlqueries to be created.
        $table = new xmldb_table('question_python_sqlqueries');

        // Adding fields to table question_python_sqlqueries.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fieldname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('query', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fields', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table question_python_sqlqueries.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'question', ['id']);

        // Conditionally launch create table for question_python_sqlqueries.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if($oldversion < 2023041000) {
        // Add field videourl in table question_sqlrunner_options
        $table = new xmldb_table('question_python_options');
        $field = new xmldb_field('videourl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'prototypeextra');

        // Conditionally launch add field template.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
