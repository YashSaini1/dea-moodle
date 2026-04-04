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

defined('MOODLE_INTERNAL') || die();

/**
 * Extra uninstall code for the CodeRunner question type.
 */

function xmldb_qtype_pythonrunner_uninstall() {
    global $DB;

    $dbman = $DB->get_manager();
    $tables = array(
        'question_python_options',
        'question_python_tests',
    );
    foreach ($tables as $table_name){
        $table = new xmldb_table($table_name);
        if ($dbman->table_exists($table_name)){
            $dbman->drop_table($table);
        }
    }

    return true;
}