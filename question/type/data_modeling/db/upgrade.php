<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Numerical question type upgrade code.
 *
 * @package    qtype
 * @subpackage data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the data_modeling question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_data_modeling_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if($oldversion < 2023041000) {
        // Add field videourl in table question_sqlrunner_options
        $table = new xmldb_table('question_dm');
        $field = new xmldb_field('videourl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'table_data');

        // Conditionally launch add field template.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
